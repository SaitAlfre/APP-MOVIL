package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.map
import pe.ecolecta.domain.acopio.RegistroAcopioCompartido
import pe.ecolecta.domain.repository.EventoRegistrosRemotos
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.domain.repository.RegistroRecibidoRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException

/**
 * "Servidor" en memoria compartido entre el celular del acopiador y el del proveedor. Igual que
 * Firestore: un documento por id (reenviar sobrescribe, nunca duplica) y el proveedor solo recibe
 * los documentos de su propio código (lo que exigen las reglas del servidor).
 */
class FakeRegistroAcopioRemoto(override val configurado: Boolean = true) : RegistroAcopioRemotoRepository {
    val documentos = MutableStateFlow<Map<String, RegistroAcopioCompartido>>(emptyMap())
    val enLinea = MutableStateFlow(true)
    var escrituras = 0
        private set

    /** Simula que el servidor rechaza (p. ej. dispositivo sin vincular): error no relacionado con la red. */
    var rechazar: Boolean = false

    /** Simula el caso más delicado: el documento SÍ llega al servidor pero la respuesta se pierde. */
    var perderRespuesta: Boolean = false

    /** Se ejecuta durante el envío: simula que el acopiador corrige el registro mientras sale. */
    var alPublicar: (suspend () -> Unit)? = null

    override suspend fun publicar(registro: RegistroAcopioCompartido): Result<Unit> {
        alPublicar?.invoke()
        if (!enLinea.value) return Result.failure(SinConexionRemotaException())
        if (rechazar) return Result.failure(IllegalStateException("PERMISSION_DENIED"))
        val actual = documentos.value[registro.id]
        if (actual == null || registro.actualizadoEn >= actual.actualizadoEn) {
            documentos.value = documentos.value + (registro.id to registro)
        }
        escrituras++
        if (perderRespuesta) return Result.failure(SinConexionRemotaException())
        return Result.success(Unit)
    }

    override fun observarDeProveedor(proveedorCodigo: String): Flow<EventoRegistrosRemotos> =
        combine(documentos, enLinea) { docs, online ->
            if (!online) {
                EventoRegistrosRemotos.SinConexion
            } else {
                EventoRegistrosRemotos.Recibidos(docs.values.filter { it.proveedorCodigo == proveedorCodigo })
            }
        }
}

class FakeRegistroRecibidoRepository : RegistroRecibidoRepository {
    val filas = MutableStateFlow<Map<String, Pair<RegistroAcopioCompartido, Long>>>(emptyMap())

    override fun observarPorCodigo(proveedorCodigo: String): Flow<List<RegistroAcopioCompartido>> =
        filas.map { m -> m.values.map { it.first }.filter { it.proveedorCodigo == proveedorCodigo }.sortedByDescending { it.registradoEn } }

    override suspend fun guardar(registros: List<RegistroAcopioCompartido>, recibidoEn: Long) {
        var m = filas.value
        registros.forEach { r ->
            val actual = m[r.id]?.first
            if (actual == null || r.actualizadoEn >= actual.actualizadoEn) m = m + (r.id to (r to recibidoEn))
        }
        filas.value = m
    }

    override suspend fun ultimaRecepcion(proveedorCodigo: String): Long? =
        filas.value.values.filter { it.first.proveedorCodigo == proveedorCodigo }.maxOfOrNull { it.second }
}
