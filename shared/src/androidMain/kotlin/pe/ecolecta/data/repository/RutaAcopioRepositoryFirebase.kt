package pe.ecolecta.data.repository

import dev.gitlive.firebase.Firebase
import dev.gitlive.firebase.firestore.firestore
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.emitAll
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.map
import kotlinx.serialization.Serializable
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.EventoRuta
import pe.ecolecta.domain.repository.RutaAcopioRepository
import java.io.IOException

/** Espejo serializable de [UbicacionAcopiador] para el documento `rutas_activas/{zonaId}`. */
@Serializable
private data class UbicacionAcopiadorRemota(
    val zonaId: String = "",
    val zonaNombre: String = "",
    val acopiadorId: String = "",
    val acopiadorNombre: String = "",
    val vehiculoId: String = "",
    val vehiculoNombre: String = "",
    val jornadaId: String = "",
    val jornadaAbiertaEn: Long = 0L,
    val fecha: String = "",
    val lat: Double = 0.0,
    val lng: Double = 0.0,
    val precisionM: Double = 0.0,
    val capturadaEn: Long = 0L,
    val secuenciaEn: Long = 0L,
    val seguimientoActivo: Boolean = false,
    val jornadaAbierta: Boolean = false,
) {
    fun aDominio() = UbicacionAcopiador(
        zonaId = zonaId,
        zonaNombre = zonaNombre,
        acopiadorId = acopiadorId,
        acopiadorNombre = acopiadorNombre,
        vehiculoId = vehiculoId,
        vehiculoNombre = vehiculoNombre,
        jornadaId = jornadaId,
        jornadaAbiertaEn = jornadaAbiertaEn,
        fecha = fecha,
        lat = lat,
        lng = lng,
        precisionM = precisionM,
        capturadaEn = capturadaEn,
        secuenciaEn = secuenciaEn,
        seguimientoActivo = seguimientoActivo,
        jornadaAbierta = jornadaAbierta,
    )

    companion object {
        fun deDominio(u: UbicacionAcopiador) = UbicacionAcopiadorRemota(
            zonaId = u.zonaId,
            zonaNombre = u.zonaNombre,
            acopiadorId = u.acopiadorId,
            acopiadorNombre = u.acopiadorNombre,
            vehiculoId = u.vehiculoId,
            vehiculoNombre = u.vehiculoNombre,
            jornadaId = u.jornadaId,
            jornadaAbiertaEn = u.jornadaAbiertaEn,
            fecha = u.fecha,
            lat = u.lat,
            lng = u.lng,
            precisionM = u.precisionM,
            capturadaEn = u.capturadaEn,
            secuenciaEn = u.secuenciaEn,
            seguimientoActivo = u.seguimientoActivo,
            jornadaAbierta = u.jornadaAbierta,
        )
    }
}

/** Documento parcial para publicarEstado: solo los campos de "reloj lógico" + estado, nunca posición. */
@Serializable
private data class EstadoRemoto(
    val zonaId: String,
    val jornadaId: String,
    val jornadaAbiertaEn: Long,
    val secuenciaEn: Long,
    val seguimientoActivo: Boolean,
    val jornadaAbierta: Boolean? = null,
)

/**
 * Implementación real con Firestore (GitLive) + Authentication anónima. Solo Android — nunca se
 * registra en commonMain/iosMain (ver `shared/build.gradle.kts` y `KoinIOS.kt`).
 *
 * La protección contra escrituras atrasadas (una posición vieja reactivando un seguimiento detenido,
 * o sobrescribiendo una jornada más nueva) vive en `firestore.rules`, no aquí: el servidor compara
 * `jornadaAbiertaEn`/`secuenciaEn` contra el documento ya guardado en cada escritura.
 *
 * [identidadRemotaProvider] es la MISMA instancia (`single` en Koin) que usa la pantalla de Perfil —
 * nunca duplicar aquí la lógica de "asegurar sesión anónima": el candado que evita crear usuarios
 * duplicados en Firebase Auth solo protege si todos los llamadores pasan por un único punto.
 */
class RutaAcopioRepositoryFirebase(
    private val identidadRemotaProvider: IdentidadRemotaProvider,
) : RutaAcopioRepository {

    private suspend fun asegurarSesionAnonima() {
        identidadRemotaProvider.obtenerUidAnonimo()
    }

    override fun observar(zonaId: String): Flow<EventoRuta> = flow {
        asegurarSesionAnonima()
        emitAll(
            Firebase.firestore.collection(COLECCION).document(zonaId).snapshots.map { snapshot ->
                if (!snapshot.exists) {
                    EventoRuta.SinDatos
                } else {
                    EventoRuta.Recibida(snapshot.data<UbicacionAcopiadorRemota>().aDominio())
                }
            },
        )
    }.catch { error -> emit(if (esErrorDeConexion(error)) EventoRuta.SinConexion else EventoRuta.NoConectado) }

    override suspend fun publicarPosicion(ubicacion: UbicacionAcopiador): Result<Unit> = runCatching {
        asegurarSesionAnonima()
        Firebase.firestore.collection(COLECCION).document(ubicacion.zonaId).set(UbicacionAcopiadorRemota.deDominio(ubicacion))
    }

    override suspend fun publicarEstado(
        zonaId: String,
        jornadaId: String,
        jornadaAbiertaEn: Long,
        secuenciaEn: Long,
        seguimientoActivo: Boolean,
        jornadaAbierta: Boolean?,
    ): Result<Unit> = runCatching {
        asegurarSesionAnonima()
        val documento = Firebase.firestore.collection(COLECCION).document(zonaId)
        val datos = EstadoRemoto(zonaId, jornadaId, jornadaAbiertaEn, secuenciaEn, seguimientoActivo, jornadaAbierta)
        // mergeFields explícitos: nunca se toca lat/lng/precisionM/capturadaEn ni (si jornadaAbierta es
        // null, caso "detener seguimiento") el campo jornadaAbierta del documento existente.
        if (jornadaAbierta != null) {
            documento.set(datos, "zonaId", "jornadaId", "jornadaAbiertaEn", "secuenciaEn", "seguimientoActivo", "jornadaAbierta")
        } else {
            documento.set(datos, "zonaId", "jornadaId", "jornadaAbiertaEn", "secuenciaEn", "seguimientoActivo")
        }
    }

    private fun esErrorDeConexion(error: Throwable): Boolean {
        var actual: Throwable? = error
        while (actual != null) {
            if (actual is IOException) return true
            actual = actual.cause
        }
        return false
    }

    private companion object {
        const val COLECCION = "rutas_activas"
    }
}
