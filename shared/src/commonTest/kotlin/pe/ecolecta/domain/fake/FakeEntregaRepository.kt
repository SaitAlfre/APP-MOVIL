package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.EntregaRepository

class FakeEntregaRepository(private val auditoriaRepository: FakeAuditoriaRepository? = null) : EntregaRepository {
    private val entregas = MutableStateFlow<List<Entrega>>(emptyList())

    fun sembrar(entrega: Entrega) {
        entregas.value = entregas.value + entrega
    }

    override suspend fun registrar(entrega: Entrega, auditoria: Auditoria) {
        entregas.value = entregas.value + entrega
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun registrarLote(entregas: List<Entrega>, auditorias: List<Auditoria>) {
        this.entregas.value = this.entregas.value + entregas
        auditorias.forEach { auditoriaRepository?.insertar(it) }
    }

    override fun observarPorJornada(jornadaId: String): Flow<List<Entrega>> =
        MutableStateFlow(entregas.value.filter { it.jornadaId == jornadaId }).asStateFlow()

    override fun observarPorProveedor(proveedorId: String): Flow<List<Entrega>> =
        MutableStateFlow(entregas.value.filter { it.proveedorId == proveedorId }.sortedByDescending { it.registradoEn }).asStateFlow()

    override suspend fun obtenerHistorial(proveedorId: String, limite: Int, desplazamiento: Int): List<Entrega> =
        entregas.value.filter { it.proveedorId == proveedorId }
            .sortedByDescending { it.registradoEn }
            .drop(desplazamiento)
            .take(limite)

    override fun observarConflictos(): Flow<List<Entrega>> =
        MutableStateFlow(entregas.value.filter { it.syncState == SyncState.CONFLICT }).asStateFlow()

    override suspend fun obtenerPorId(id: String): Entrega? = entregas.value.firstOrNull { it.id == id }

    override suspend fun filtrar(
        jornadaId: String?,
        proveedorId: String?,
        usuarioId: String?,
        zonaId: String?,
        vehiculoId: String?,
        syncState: SyncState?,
        loteId: String?,
    ): List<Entrega> = entregas.value.filter { e ->
        (jornadaId == null || e.jornadaId == jornadaId) &&
            (proveedorId == null || e.proveedorId == proveedorId) &&
            (usuarioId == null || e.usuarioId == usuarioId) &&
            (zonaId == null || e.zonaId == zonaId) &&
            (vehiculoId == null || e.vehiculoId == vehiculoId) &&
            (syncState == null || e.syncState == syncState) &&
            (loteId == null || e.loteId == loteId)
    }

    override fun observarConFiltros(
        jornadaId: String?,
        proveedorId: String?,
        usuarioId: String?,
        zonaId: String?,
        vehiculoId: String?,
        syncState: SyncState?,
        loteId: String?,
    ): Flow<List<Entrega>> = MutableStateFlow(
        entregas.value.filter { e ->
            (jornadaId == null || e.jornadaId == jornadaId) &&
                (proveedorId == null || e.proveedorId == proveedorId) &&
                (usuarioId == null || e.usuarioId == usuarioId) &&
                (zonaId == null || e.zonaId == zonaId) &&
                (vehiculoId == null || e.vehiculoId == vehiculoId) &&
                (syncState == null || e.syncState == syncState) &&
                (loteId == null || e.loteId == loteId)
        },
    ).asStateFlow()

    override suspend fun corregir(id: String, litros: Double, tachos: Int, observaciones: String?, updatedAt: Long, auditoria: Auditoria) {
        entregas.value = entregas.value.map {
            if (it.id == id) it.copy(litros = litros, tachos = tachos, observaciones = observaciones, updatedAt = updatedAt) else it
        }
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun anular(id: String, updatedAt: Long, auditoria: Auditoria) {
        entregas.value = entregas.value.map { if (it.id == id) it.copy(anulada = true, updatedAt = updatedAt) else it }
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun resolverConflicto(id: String, litros: Double, tachos: Int, syncState: SyncState, updatedAt: Long, auditoria: Auditoria) {
        entregas.value = entregas.value.map {
            if (it.id == id) {
                it.copy(
                    litros = litros,
                    tachos = tachos,
                    syncState = syncState,
                    litrosServidor = null,
                    tachosServidor = null,
                    motivoConflicto = null,
                    updatedAt = updatedAt,
                )
            } else {
                it
            }
        }
        auditoriaRepository?.insertar(auditoria)
    }

    override suspend fun sumaLitrosEntreFechas(desde: Long, hasta: Long): Double =
        entregas.value.filter { !it.anulada && it.registradoEn in desde until hasta }.sumOf { it.litros }

    override suspend fun contarEntregasEntreFechas(desde: Long, hasta: Long): Long =
        entregas.value.count { !it.anulada && it.registradoEn in desde until hasta }.toLong()

    override suspend fun contarPendientes(): Long = entregas.value.count { it.syncState == SyncState.PENDING }.toLong()

    override suspend fun contarError(): Long = entregas.value.count { it.syncState == SyncState.ERROR }.toLong()

    override suspend fun contarConflicto(): Long = entregas.value.count { it.syncState == SyncState.CONFLICT }.toLong()
}
