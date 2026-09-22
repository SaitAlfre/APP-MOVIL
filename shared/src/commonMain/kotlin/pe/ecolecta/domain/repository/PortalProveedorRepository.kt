package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor

interface PortalProveedorRepository {
    fun solicitudes(proveedorId: String): Flow<List<SolicitudProveedor>>
    fun pagos(proveedorId: String): Flow<List<PagoProveedor>>
    suspend fun guardar(solicitud: SolicitudProveedor)
}
