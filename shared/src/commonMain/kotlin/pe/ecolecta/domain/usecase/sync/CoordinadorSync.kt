package pe.ecolecta.domain.usecase.sync

import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import pe.ecolecta.domain.security.Sha256
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.sync.Mutex
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.*

data class EstadoSincronizacion(
    val sincronizando: Boolean = false,
    val pendientes: Int = 0,
    val errores: Int = 0,
    val novedades: Boolean = false,
    val verificado: Boolean = false,
    val sinConexion: Boolean = false,
    val problema: String? = null,
    val ultimaSync: Long? = null,
    val automatico: Boolean = true,
    val cuenta: String = "",
    val servidor: String = "",
    val requiereEnlace: Boolean = false,
    val otrosPendientes: Int = 0,

) {
    val texto: String get() = when {
        sincronizando -> "Sincronizando…"
        sinConexion -> "Sin conexión · $pendientes por enviar"
        requiereEnlace -> "Confirma tu acceso para sincronizar"
        problema != null -> "No se completó la sincronización"
        errores > 0 -> "$errores cambios requieren revisión"
        pendientes > 0 && novedades -> "$pendientes por enviar · Hay novedades"
        pendientes > 0 -> "$pendientes cambios por enviar"
        novedades -> "Hay cambios por descargar"
        verificado -> "Todo sincronizado"
        else -> "Sincronización sin comprobar"
    }
}

/** Botón, gesto y ciclo comparten exclusión: no se anuncian éxitos parciales. */
class CoordinadorSync(
    private val registros: SincronizarRegistrosAcopioUseCase,
    private val enviar: EnviarCambiosServidorUseCase,
    private val servidor: ServidorWebRepository,
    private val local: DatosServidorLocalRepository,
    private val sesiones: SesionRepository,
    private val cambios: CambiosLocalesRepository,
    private val entregas: EntregaRepository,
    private val sinRecojo: SinRecojoRepository,
    private val db: EcolectaDatabase,
    private val reloj: Reloj,
    private val preparador: PreparadorEntregaServidor? = null,
) {
    private val mutex = Mutex()
    private val _estado = MutableStateFlow(EstadoSincronizacion())
    val estado = _estado.asStateFlow()
    private var usuario: String? = null
    private var ultimaCopia: String? = null

    suspend fun actualizarPendientes() {
        val id = sesiones.observar().first()?.usuario?.id
        if (id != usuario) {
            usuario = id
            id?.let { db.preferenciaSyncQueries.inicializar(it) }
            ultimaCopia = null
            val preferencia = id?.let { db.preferenciaSyncQueries.obtener(it).executeAsOneOrNull() }
            ultimaCopia = preferencia?.huella
            _estado.value = EstadoSincronizacion(
                automatico = preferencia?.automatico != 0L,
                ultimaSync = preferencia?.ultima_sync,
                cuenta = sesiones.observar().first()?.usuario?.username.orEmpty(),
                servidor = servidor.direccion.orEmpty(),
            )
        }
        val filas = entregas.filtrar()
        val propias = filas.filter { (preparador?.remitente(it) ?: it.usuarioId) == id }
        val otras = filas.filter { (preparador?.remitente(it) ?: it.usuarioId) != id }
        val cola = db.cambioPendienteQueries.pendientes().executeAsList().filter { it.usuario_id == id }
        val fallidos = db.cambioPendienteQueries.errores().executeAsList().filter { it.usuario_id == id }
        _estado.value = _estado.value.copy(
            pendientes = propias.count { it.syncState == SyncState.PENDING || it.syncState == SyncState.SYNCING } +
                sinRecojo.pendientesDeSincronizar().count { it.usuarioId == id } + cola.size,
            errores = propias.count { it.syncState == SyncState.ERROR || it.syncState == SyncState.CONFLICT } + fallidos.size,
            otrosPendientes = otras.count { it.syncState != SyncState.SYNCED },
        )
    }

    /** El PIN vive solo durante esta petición; no se guarda en preferencias ni en la cola. */
    suspend fun vincular(pin: String): String? {
        val sesion = sesiones.observar().first() ?: return "Inicia sesión en la app."
        val resultado = servidor.vincular(sesion.usuario.id, sesion.usuario.username, pin)
        val error = resultado.exceptionOrNull()
        if (error != null) {
            _estado.value = _estado.value.copy(problema = error.message, requiereEnlace = true, verificado = false)
            return error.message ?: "No se pudo vincular la cuenta con el panel."
        }
        _estado.value = _estado.value.copy(requiereEnlace = false, problema = null)
        return ejecutar(manual = true)
    }

    fun automatico(activado: Boolean) {
        usuario?.let { db.preferenciaSyncQueries.guardarAutomatico(if (activado) 1L else 0L, it) }
        _estado.value = _estado.value.copy(automatico = activado)
    }

    suspend fun ejecutar(manual: Boolean = false): String? {
        if (!mutex.tryLock()) return null
        try {
            actualizarPendientes()
            val id = usuario ?: return null
            if (manual) db.cambioPendienteQueries.reintentarErrores(id)
            val aplicar = manual || _estado.value.automatico
            _estado.value = _estado.value.copy(sincronizando = aplicar, problema = null)
            if (!servidor.configurado) error("El panel web no está configurado. Tus datos siguen en este teléfono.")
            var advertencia: String? = null
            if (aplicar) {
                val resultado = registros(id)
                val otros = enviar(id)
                if (resultado.sinConexion > 0 || otros.sinConexion) advertencia = "Hay cambios por enviar; se volverá a intentar al conectar."
                if (resultado.sinEnlazar > 0) advertencia = "Hay cuentas sin enlazar. Inicia sesión con conexión para enviar sus cambios."
                if (resultado.fallidos > 0 || otros.conError > 0) advertencia = "Algunos cambios fueron rechazados. Revisa los detalles de sincronización."
            }
            val datos = servidor.descargarDatos(id).getOrThrow()
            if (sesiones.observar().first()?.usuario?.id != id) return null
            val normalizada = datos.copy(servidorEn = 0,
                zonas = datos.zonas.sortedBy { it.id }, vehiculos = datos.vehiculos.sortedBy { it.id },
                usuarios = datos.usuarios.sortedBy { it.id }, proveedores = datos.proveedores.sortedBy { it.id },
                jornadas = datos.jornadas.sortedBy { it.id }, entregas = datos.entregas.sortedBy { it.id },
                analisis = datos.analisis.sortedBy { it.id }, comunicados = datos.comunicados.sortedBy { it.id },
                pagos = datos.pagos?.sortedBy { it.id }, solicitudes = datos.solicitudes?.sortedBy { it.id }, sinRecojos = datos.sinRecojos?.sortedBy { it.id })
            val comparable = Sha256.hash(Json.encodeToString(normalizada).encodeToByteArray())
                .joinToString("") { (it.toInt() and 255).toString(16).padStart(2, '0') }
            if (aplicar) {
                local.aplicar(datos)
                ultimaCopia = comparable
                db.preferenciaSyncQueries.guardarHuella(comparable, id)
                actualizarPendientes()
                val completo = advertencia == null && _estado.value.pendientes == 0 && _estado.value.errores == 0
                val ahora = reloj.ahora().toEpochMilliseconds()
                if (completo) db.preferenciaSyncQueries.guardarUltima(ahora, id)
                _estado.value = _estado.value.copy(
                    novedades = false, sinConexion = false, requiereEnlace = false, verificado = completo, problema = advertencia,
                    ultimaSync = if (completo) ahora else _estado.value.ultimaSync,
                )
                return if (completo) "Sincronización completada" else "Datos actualizados. Quedan cambios pendientes; revisa los detalles."
            }
            // Huella persistida: la hora de respuesta no cuenta como novedad al reiniciar.
            _estado.value = _estado.value.copy(novedades = ultimaCopia != comparable, sinConexion = false)
            return null
        } catch (e: CancellationException) {
            throw e
        } catch (e: Exception) {
            val offline = e is SinConexionRemotaException
            _estado.value = _estado.value.copy(
                verificado = false, sinConexion = offline, requiereEnlace = e is SinSesionServidorException,
                problema = if (offline) null else (e.message ?: "No se pudo sincronizar"),
            )
            return if (offline) "Sin conexión. Tus cambios están guardados en este teléfono." else _estado.value.problema
        } finally {
            _estado.value = _estado.value.copy(sincronizando = false)
            mutex.unlock()
        }
    }
}
