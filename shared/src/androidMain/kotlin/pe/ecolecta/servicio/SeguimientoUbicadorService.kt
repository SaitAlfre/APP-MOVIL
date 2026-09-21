package pe.ecolecta.servicio

import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.app.Service
import android.content.Intent
import android.content.pm.ServiceInfo
import android.os.Build
import android.os.IBinder
import android.util.Log
import androidx.core.app.NotificationCompat
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.cancel
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.launchIn
import kotlinx.coroutines.flow.onEach
import kotlinx.coroutines.launch
import org.koin.core.component.KoinComponent
import org.koin.core.component.inject
import pe.ecolecta.domain.EventoUbicacion
import pe.ecolecta.domain.LocationProvider
import pe.ecolecta.domain.aEstadoSeguimiento
import pe.ecolecta.domain.excepcionAEstadoSeguimiento
import pe.ecolecta.domain.model.ContextoPublicacionRuta
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.repository.EstadoSeguimientoRepository
import pe.ecolecta.domain.repository.UbicacionAcopiadorLocalRepository
import pe.ecolecta.domain.usecase.seguimiento.GuardarUbicacionLocalUseCase
import pe.ecolecta.domain.usecase.seguimiento.ObtenerContextoPublicacionRutaUseCase
import pe.ecolecta.domain.usecase.seguimiento.PublicarUbicacionRemotaUseCase

/**
 * Servicio en primer plano que captura y guarda localmente la ubicación del ACOPIADOR mientras el
 * seguimiento está activo. Solo corre mientras haya una jornada abierta y el usuario lo haya iniciado
 * (ver [pe.ecolecta.domain.usecase.seguimiento.IniciarSeguimientoUseCase]/[pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase]).
 *
 * La publicación remota (Firestore) corre en su propio job ([publicacionJob]), separado del job de
 * captura GPS ([capturaJob]): un fallo o demora de red nunca bloquea ni cancela la siguiente captura o
 * el guardado local (§ Grupo 5, punto 3). Ese mismo job reintenta periódicamente la última posición
 * guardada localmente mientras siga sin publicarse, aunque el acopiador esté quieto y no lleguen
 * eventos nuevos del GPS.
 */
class SeguimientoUbicadorService : Service(), KoinComponent {

    private val locationProvider: LocationProvider by inject()
    private val guardarUbicacionLocalUseCase: GuardarUbicacionLocalUseCase by inject()
    private val estadoSeguimientoRepository: EstadoSeguimientoRepository by inject()
    private val obtenerContextoPublicacionRutaUseCase: ObtenerContextoPublicacionRutaUseCase by inject()
    private val publicarUbicacionRemotaUseCase: PublicarUbicacionRemotaUseCase by inject()
    private val ubicacionAcopiadorLocalRepository: UbicacionAcopiadorLocalRepository by inject()

    private val scope = CoroutineScope(SupervisorJob() + Dispatchers.Main.immediate)
    private var capturaJob: Job? = null
    private var publicacionJob: Job? = null

    @Volatile
    private var contextoActual: ContextoPublicacionRuta? = null

    override fun onBind(intent: Intent?): IBinder? = null

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        Log.d(TAG, "onStartCommand: action=${intent?.action}")

        // Android exige llamar startForeground() dentro de los primeros segundos de creado el
        // servicio (arrancó vía startForegroundService()) o el sistema mata el proceso con
        // ForegroundServiceDidNotStartInTimeException. Se llama primero que cualquier otra cosa —
        // antes de validar el Intent — para cubrir cualquier caller, incluso uno con extras faltantes
        // o inválidos, que de otro modo retornaría sin haberlo llamado nunca.
        if (!iniciarForegroundMinimo()) {
            scope.launch { finalizarConError(IllegalStateException("No se pudo iniciar el servicio en primer plano.")) }
            return START_NOT_STICKY
        }

        if (intent?.action == ACCION_DETENER) {
            detener()
            return START_NOT_STICKY
        }

        val usuarioId = intent?.getStringExtra(EXTRA_USUARIO_ID)
        val jornadaId = intent?.getStringExtra(EXTRA_JORNADA_ID)
        val zonaId = intent?.getStringExtra(EXTRA_ZONA_ID)
        if (usuarioId == null || jornadaId == null || zonaId == null) {
            Log.e(TAG, "onStartCommand: faltan extras (usuarioId/jornadaId/zonaId), deteniendo servicio")
            scope.launch { finalizarConError(IllegalArgumentException("Faltan los datos de la jornada.")) }
            return START_NOT_STICKY
        }
        iniciar(usuarioId, jornadaId, zonaId)
        return START_NOT_STICKY
    }

    /** @return false si startForeground() falló y el servicio no puede continuar. */
    private fun iniciarForegroundMinimo(): Boolean = try {
        crearCanalNotificacion()
        val notificacion = construirNotificacion()
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            startForeground(NOTIFICACION_ID, notificacion, ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION)
        } else {
            startForeground(NOTIFICACION_ID, notificacion)
        }
        Log.d(TAG, "startForeground OK")
        true
    } catch (error: Exception) {
        Log.e(TAG, "startForeground falló: el servicio no puede continuar", error)
        false
    }

    private fun iniciar(usuarioId: String, jornadaId: String, zonaId: String) {
        Log.d(TAG, "iniciar: usuarioId=$usuarioId jornadaId=$jornadaId zonaId=$zonaId")
        capturaJob?.cancel()
        publicacionJob?.cancel()
        contextoActual = null

        capturaJob = locationProvider.observarUbicacion()
            .onEach { evento ->
                // Solo eventos reales cambian el estado (nunca la sola ausencia de uno nuevo por
                // quietud): pe.ecolecta.domain.aEstadoSeguimiento() documenta esa correspondencia.
                when (evento) {
                    is EventoUbicacion.Capturada -> {
                        val ubicacion = evento.ubicacion
                        Log.d(TAG, "ubicación capturada: lat=${ubicacion.lat} lng=${ubicacion.lng} precision=${ubicacion.precisionM}")
                        guardarUbicacionLocalUseCase(
                            usuarioId = usuarioId,
                            jornadaId = jornadaId,
                            zonaId = zonaId,
                            lat = ubicacion.lat,
                            lng = ubicacion.lng,
                            precisionM = ubicacion.precisionM,
                            capturadaEn = ubicacion.timestamp,
                            publicada = false,
                        )
                        Log.d(TAG, "ubicación guardada: capturadaEn=${ubicacion.timestamp}")
                        publicarEnParalelo(ubicacion.lat, ubicacion.lng, ubicacion.precisionM, ubicacion.timestamp)
                    }
                    EventoUbicacion.SenalPerdida -> Log.w(TAG, "señal de ubicación perdida (proveedor del sistema desactivado)")
                    EventoUbicacion.SenalRecuperada -> Log.d(TAG, "proveedor de ubicación reactivado, buscando fix nuevo")
                }
                estadoSeguimientoRepository.actualizar(evento.aEstadoSeguimiento())
            }
            .catch { error ->
                finalizarConError(error)
            }
            .launchIn(scope)

        publicacionJob = scope.launch {
            contextoActual = runCatching { obtenerContextoPublicacionRutaUseCase(usuarioId, jornadaId) }
                .onFailure { Log.w(TAG, "no se pudo resolver el contexto de publicación remota", it) }
                .getOrNull()
            while (true) {
                delay(REINTENTO_PUBLICACION_MS)
                reintentarUltimaPosicionNoPublicada(usuarioId)
            }
        }
    }

    /** Nunca bloquea ni cancela [capturaJob]: corre en su propio hijo del scope, con su propio catch. */
    private fun publicarEnParalelo(lat: Double, lng: Double, precisionM: Double, capturadaEn: Long) {
        val contexto = contextoActual ?: return
        scope.launch {
            runCatching { publicarUbicacionRemotaUseCase(contexto, lat, lng, precisionM, capturadaEn) }
                .onFailure { Log.w(TAG, "publicación remota falló, se reintentará", it) }
        }
    }

    private suspend fun reintentarUltimaPosicionNoPublicada(usuarioId: String) {
        val contexto = contextoActual ?: return
        val local = runCatching { ubicacionAcopiadorLocalRepository.obtener(usuarioId) }.getOrNull() ?: return
        // Descarta una fila de una jornada distinta a la que este servicio está publicando (jornada
        // cerrada/reabierta entretanto): nunca reintentar una posición que ya no corresponde.
        if (local.publicada || local.jornadaId != contexto.jornadaId) return
        runCatching { publicarUbicacionRemotaUseCase(contexto, local.lat, local.lng, local.precisionM, local.capturadaEn) }
            .onFailure { Log.w(TAG, "reintento periódico de publicación falló", it) }
    }

    private suspend fun finalizarConError(error: Throwable) {
        Log.e(TAG, "seguimiento detenido por ${error.javaClass.simpleName}", error)
        val estado = if (error is SecurityException) {
            EstadoSeguimiento.PERMISO_DENEGADO
        } else {
            excepcionAEstadoSeguimiento(error)
        }
        estadoSeguimientoRepository.actualizar(estado)
        // El flujo ya terminó: no dejar una notificación que afirme que la captura continúa.
        stopForeground(STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    private fun detener() {
        Log.d(TAG, "detener")
        capturaJob?.cancel()
        publicacionJob?.cancel()
        scope.launch { estadoSeguimientoRepository.actualizar(EstadoSeguimiento.INACTIVO) }
        stopForeground(STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    override fun onDestroy() {
        Log.d(TAG, "onDestroy")
        capturaJob?.cancel()
        publicacionJob?.cancel()
        scope.cancel()
        super.onDestroy()
    }

    private fun crearCanalNotificacion() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val manager = getSystemService(NotificationManager::class.java)
            val canal = NotificationChannel(CANAL_ID, "Seguimiento de ubicación", NotificationManager.IMPORTANCE_LOW)
            manager.createNotificationChannel(canal)
        }
    }

    private fun construirNotificacion(): Notification {
        val abrirApp = packageManager.getLaunchIntentForPackage(packageName)?.let { launchIntent ->
            PendingIntent.getActivity(this, 0, launchIntent, PendingIntent.FLAG_IMMUTABLE)
        }
        val detenerIntent = PendingIntent.getService(
            this,
            0,
            Intent(this, SeguimientoUbicadorService::class.java).apply { action = ACCION_DETENER },
            PendingIntent.FLAG_IMMUTABLE,
        )
        return NotificationCompat.Builder(this, CANAL_ID)
            .setContentTitle("Seguimiento activo — Ecolecta")
            .setContentText("Los proveedores de tu zona pueden ver tu ubicación mientras dure la jornada.")
            .setSmallIcon(android.R.drawable.ic_menu_mylocation)
            .setOngoing(true)
            .setContentIntent(abrirApp)
            .addAction(0, "Detener", detenerIntent)
            .build()
    }

    companion object {
        const val ACCION_DETENER = "pe.ecolecta.seguimiento.DETENER"
        const val EXTRA_USUARIO_ID = "usuarioId"
        const val EXTRA_JORNADA_ID = "jornadaId"
        const val EXTRA_ZONA_ID = "zonaId"
        private const val CANAL_ID = "seguimiento_ubicacion"
        private const val NOTIFICACION_ID = 4821
        private const val TAG = "EcolectaUbicacion"
        private const val REINTENTO_PUBLICACION_MS = 25_000L
    }
}
