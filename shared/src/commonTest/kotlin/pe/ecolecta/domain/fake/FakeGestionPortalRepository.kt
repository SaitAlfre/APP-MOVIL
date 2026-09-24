package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.domain.repository.GestionPortalRepository

class FakeGestionPortalRepository : GestionPortalRepository {
    val pagos = MutableStateFlow<List<PagoProveedor>>(emptyList())
    val solicitudes = MutableStateFlow<List<SolicitudProveedor>>(emptyList())

    override fun todasSolicitudes(): Flow<List<SolicitudProveedor>> = solicitudes
    override fun todosPagos(): Flow<List<PagoProveedor>> = pagos

    override suspend fun actualizarSolicitud(solicitud: SolicitudProveedor) {
        solicitudes.value = solicitudes.value.map { if (it.id == solicitud.id) solicitud else it }
    }

    override suspend fun guardarPagos(pagos: List<PagoProveedor>, en: Long) {
        val ids = pagos.map { it.id }.toSet()
        this.pagos.value = this.pagos.value.filterNot { it.id in ids } + pagos
    }

    fun liquidar(desde: String, proveedorId: String, estado: String) {
        pagos.value = pagos.value + PagoProveedor(
            id = "liq-$desde-$proveedorId", proveedorId = proveedorId, desde = desde, hasta = desde,
            litros = 18.0, precio = 1.8, bruto = 32.4, descuento = 0.0, total = 32.4, estado = estado,
        )
    }
}
