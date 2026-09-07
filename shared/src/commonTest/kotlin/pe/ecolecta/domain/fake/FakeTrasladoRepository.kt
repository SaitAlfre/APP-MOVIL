package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.repository.TrasladoRepository

/** Replica el efecto colateral real: autorizar también mueve al proveedor a la zona destino (§9). */
class FakeTrasladoRepository(
    private val proveedorRepository: FakeProveedorRepository? = null,
    private val auditoriaRepository: FakeAuditoriaRepository? = null,
) : TrasladoRepository {
    private val traslados = MutableStateFlow<List<TrasladoZona>>(emptyList())

    override fun observarTodos(): Flow<List<TrasladoZona>> = traslados.asStateFlow()

    override fun observarPendientes(): Flow<List<TrasladoZona>> =
        MutableStateFlow(traslados.value.filter { it.estado == EstadoTraslado.PENDIENTE }).asStateFlow()

    override suspend fun obtenerPorId(id: String): TrasladoZona? = traslados.value.firstOrNull { it.id == id }

    override suspend fun insertar(traslado: TrasladoZona) {
        traslados.value = traslados.value + traslado
    }

    override suspend fun autorizar(
        id: String,
        autorizadoPor: String,
        proveedorId: String,
        nuevaZonaId: String,
        updatedAtProveedor: Long,
        auditoria: Auditoria,
    ) {
        traslados.value = traslados.value.map {
            if (it.id == id) it.copy(estado = EstadoTraslado.AUTORIZADO, autorizadoPor = autorizadoPor) else it
        }
        proveedorRepository?.cambiarZona(proveedorId, nuevaZonaId, updatedAtProveedor)
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun rechazar(id: String, autorizadoPor: String, auditoria: Auditoria) {
        traslados.value = traslados.value.map {
            if (it.id == id) it.copy(estado = EstadoTraslado.RECHAZADO, autorizadoPor = autorizadoPor) else it
        }
        auditoriaRepository?.insertar(auditoria)
    }
}
