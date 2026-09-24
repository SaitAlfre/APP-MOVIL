package pe.ecolecta.data.repository

import dev.gitlive.firebase.Firebase
import dev.gitlive.firebase.firestore.FirebaseFirestoreException
import dev.gitlive.firebase.firestore.FirestoreExceptionCode
import dev.gitlive.firebase.firestore.code
import dev.gitlive.firebase.firestore.firestore
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.TimeoutCancellationException
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.flow.emitAll
import kotlinx.coroutines.flow.flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withTimeout
import kotlinx.serialization.Serializable
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.acopio.RegistroAcopioCompartido
import pe.ecolecta.domain.acopio.TipoRegistroCompartido
import pe.ecolecta.domain.repository.EventoRegistrosRemotos
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import java.io.IOException

/** Documento `registros_acopio/{id}`. Solo el código del proveedor dueño: nunca nombres de terceros. */
@Serializable
private data class RegistroAcopioRemoto(
    val id: String = "",
    val tipo: String = "",
    val proveedorCodigo: String = "",
    val zonaId: String = "",
    val jornadaId: String = "",
    val acopiadorId: String = "",
    val acopiadorNombre: String = "",
    val fecha: String = "",
    val registradoEn: Long = 0L,
    val litros: Double? = null,
    val tachos: Int? = null,
    val anulada: Boolean = false,
    val motivo: String? = null,
    val detalle: String? = null,
    val deshecha: Boolean = false,
    val actualizadoEn: Long = 0L,
) {
    fun aDominio(): RegistroAcopioCompartido? {
        val tipoDominio = TipoRegistroCompartido.entries.firstOrNull { it.name == tipo } ?: return null
        return RegistroAcopioCompartido(
            id = id, tipo = tipoDominio, proveedorCodigo = proveedorCodigo, zonaId = zonaId, jornadaId = jornadaId,
            acopiadorId = acopiadorId, acopiadorNombre = acopiadorNombre, fecha = fecha, registradoEn = registradoEn,
            litros = litros, tachos = tachos, anulada = anulada, motivo = motivo, detalle = detalle, deshecha = deshecha,
            actualizadoEn = actualizadoEn,
        )
    }

    companion object {
        fun deDominio(r: RegistroAcopioCompartido) = RegistroAcopioRemoto(
            id = r.id, tipo = r.tipo.name, proveedorCodigo = r.proveedorCodigo, zonaId = r.zonaId, jornadaId = r.jornadaId,
            acopiadorId = r.acopiadorId, acopiadorNombre = r.acopiadorNombre, fecha = r.fecha, registradoEn = r.registradoEn,
            litros = r.litros, tachos = r.tachos, anulada = r.anulada, motivo = r.motivo, detalle = r.detalle,
            deshecha = r.deshecha, actualizadoEn = r.actualizadoEn,
        )
    }
}

/**
 * Sincronización real entre celulares con Firestore (GitLive) + Authentication anónima. Solo Android,
 * igual que antes el seguimiento GPS: nunca se registra en commonMain/iosMain.
 *
 * Autorización en `firestore.rules`: el acopiador solo escribe registros de la zona de su vínculo
 * (`acopiador_links/{uid}`), y el proveedor solo lee los documentos cuyo `proveedorCodigo` coincide
 * con su vínculo (`proveedor_links/{uid}.proveedorCodigo`), ambos creados a mano en Firebase Console.
 *
 * `set()` de Firestore no termina hasta que el servidor confirma; sin conexión se corta a los
 * [ESPERA_MS] y se informa [SinConexionRemotaException]. Firestore conserva la escritura en su cola y
 * el siguiente reintento escribe el MISMO documento (mismo id), así que no hay duplicados.
 */
class RegistroAcopioRemotoRepositoryFirebase(
    private val identidadRemotaProvider: IdentidadRemotaProvider,
) : RegistroAcopioRemotoRepository {

    override val configurado: Boolean = true

    override suspend fun publicar(registro: RegistroAcopioCompartido): Result<Unit> = try {
        withTimeout(ESPERA_MS) {
            identidadRemotaProvider.obtenerUidAnonimo() ?: throw SinConexionRemotaException()
            Firebase.firestore.collection(COLECCION).document(registro.id).set(RegistroAcopioRemoto.deDominio(registro))
        }
        Result.success(Unit)
    } catch (e: TimeoutCancellationException) {
        Result.failure(SinConexionRemotaException(e))
    } catch (e: CancellationException) {
        throw e
    } catch (e: Exception) {
        Result.failure(if (esErrorDeConexion(e)) SinConexionRemotaException(e) else e)
    }

    override fun observarDeProveedor(proveedorCodigo: String): Flow<EventoRegistrosRemotos> = flow {
        identidadRemotaProvider.obtenerUidAnonimo() ?: run {
            emit(EventoRegistrosRemotos.SinConexion)
            return@flow
        }
        emitAll(
            Firebase.firestore.collection(COLECCION)
                // El filtro es obligatorio: las reglas solo aceptan consultas por el propio código.
                .where { "proveedorCodigo" equalTo proveedorCodigo }
                .snapshots(includeMetadataChanges = true)
                .map { snapshot ->
                    EventoRegistrosRemotos.Recibidos(
                        registros = snapshot.documents
                            .mapNotNull { runCatching { it.data<RegistroAcopioRemoto>().aDominio() }.getOrNull() }
                            .filter { it.proveedorCodigo == proveedorCodigo },
                        desdeCache = snapshot.metadata.isFromCache,
                    )
                },
        )
    }.catch { error ->
        emit(
            if (esErrorDeConexion(error)) {
                EventoRegistrosRemotos.SinConexion
            } else {
                EventoRegistrosRemotos.NoDisponible(
                    if (error is FirebaseFirestoreException && error.code == FirestoreExceptionCode.PERMISSION_DENIED) {
                        "Tu cuenta aún no está vinculada para recibir registros. Pide al administrador que la vincule."
                    } else {
                        "No se pudo consultar el servidor."
                    },
                )
            },
        )
    }

    private fun esErrorDeConexion(error: Throwable): Boolean {
        var actual: Throwable? = error
        while (actual != null) {
            if (actual is IOException || actual is SinConexionRemotaException) return true
            if (actual is FirebaseFirestoreException &&
                (actual.code == FirestoreExceptionCode.UNAVAILABLE || actual.code == FirestoreExceptionCode.DEADLINE_EXCEEDED)
            ) {
                return true
            }
            actual = actual.cause
        }
        return false
    }

    private companion object {
        const val COLECCION = "registros_acopio"
        const val ESPERA_MS = 15_000L
    }
}
