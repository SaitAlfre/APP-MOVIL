package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor

interface PortalProveedorRepository {
    fun solicitudes(proveedorId: String): Flow<List<SolicitudProveedor>>
    fun pagos(proveedorId: String): Flow<List<PagoProveedor>>
    fun precios(proveedorId: String): Flow<pe.ecolecta.domain.model.PreciosProveedor> = kotlinx.coroutines.flow.flowOf(pe.ecolecta.domain.model.PreciosProveedor())
    suspend fun guardar(solicitud: SolicitudProveedor)
}
