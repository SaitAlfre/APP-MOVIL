package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.data.local.EntidadCambio
import pe.ecolecta.data.local.marcarCambio

class PortalProveedorRepository(private val db: EcolectaDatabase) : pe.ecolecta.domain.repository.PortalProveedorRepository {
    private val json = Json { ignoreUnknownKeys = true }
    override fun solicitudes(proveedorId: String) = db.portalProveedorQueries.porProveedor(proveedorId)
        .asFlow().mapToList(Dispatchers.Default).map { rows ->
            rows.filter { it.tipo == "SOLICITUD" }.map { json.decodeFromString<SolicitudProveedor>(it.contenido) }
        }
    override fun pagos(proveedorId: String) = db.portalProveedorQueries.porProveedor(proveedorId)
        .asFlow().mapToList(Dispatchers.Default).map { rows ->
            rows.filter { it.tipo == "PAGO" }.map { json.decodeFromString<PagoProveedor>(it.contenido) }
        }
    override suspend fun guardar(solicitud: SolicitudProveedor) = withContext(Dispatchers.Default) {
        db.portalProveedorQueries.guardar(solicitud.id, solicitud.proveedorId, "SOLICITUD", json.encodeToString(solicitud), solicitud.creadaEn)
        db.marcarCambio(EntidadCambio.SOLICITUD, solicitud.id)
        Unit
    }
}
