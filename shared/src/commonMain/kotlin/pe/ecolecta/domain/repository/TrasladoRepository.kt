package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.TrasladoZona

interface TrasladoRepository {
    fun observarTodos(): Flow<List<TrasladoZona>>
    fun observarPendientes(): Flow<List<TrasladoZona>>
    suspend fun obtenerPorId(id: String): TrasladoZona?
    suspend fun insertar(traslado: TrasladoZona)

    /** Autoriza el traslado y actualiza la zona del proveedor en una única transacción (§9). */
    suspend fun autorizar(id: String, autorizadoPor: String, proveedorId: String, nuevaZonaId: String, updatedAtProveedor: Long, auditoria: Auditoria)

    suspend fun rechazar(id: String, autorizadoPor: String, auditoria: Auditoria)
}
