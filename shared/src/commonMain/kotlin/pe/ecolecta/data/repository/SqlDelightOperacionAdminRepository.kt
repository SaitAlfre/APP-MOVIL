package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Comunicado
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.domain.repository.AlertaDescartadaRepository
import pe.ecolecta.domain.repository.ComunicadoRepository
import pe.ecolecta.domain.repository.GestionPortalRepository

class SqlDelightGestionPortalRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : GestionPortalRepository {
    private val json = Json { ignoreUnknownKeys = true }

    override fun todasSolicitudes(): Flow<List<SolicitudProveedor>> =
        db.portalProveedorQueries.porTipo("SOLICITUD").asFlow().mapToList(dispatcher)
            .map { filas -> filas.mapNotNull { runCatching { json.decodeFromString<SolicitudProveedor>(it.contenido) }.getOrNull() } }

    override fun todosPagos(): Flow<List<PagoProveedor>> =
        db.portalProveedorQueries.porTipo("PAGO").asFlow().mapToList(dispatcher)
            .map { filas -> filas.mapNotNull { runCatching { json.decodeFromString<PagoProveedor>(it.contenido) }.getOrNull() } }

    override suspend fun actualizarSolicitud(solicitud: SolicitudProveedor) = withContext(dispatcher) {
        db.portalProveedorQueries.actualizarContenido(json.encodeToString(solicitud), solicitud.id)
        Unit
    }

    override suspend fun guardarPagos(pagos: List<PagoProveedor>, en: Long) = withContext(dispatcher) {
        db.transaction {
            pagos.forEach { pago ->
                val contenido = json.encodeToString(pago)
                val existe = db.portalProveedorQueries.porProveedor(pago.proveedorId).executeAsList().any { it.id == pago.id }
                if (existe) {
                    db.portalProveedorQueries.actualizarContenido(contenido, pago.id)
                } else {
                    db.portalProveedorQueries.guardar(pago.id, pago.proveedorId, "PAGO", contenido, en)
                }
            }
        }
    }
}

class SqlDelightComunicadoRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : ComunicadoRepository {
    override fun observarTodos(): Flow<List<Comunicado>> =
        db.comunicadoQueries.selectTodos().asFlow().mapToList(dispatcher).map { filas ->
            filas.map { Comunicado(it.id, it.mensaje, it.autor_id, it.autor_nombre, it.publicado_en) }
        }

    override suspend fun publicar(comunicado: Comunicado) = withContext(dispatcher) {
        db.comunicadoQueries.insertar(comunicado.id, comunicado.mensaje, comunicado.autorId, comunicado.autorNombre, comunicado.publicadoEn)
        Unit
    }

    override suspend fun eliminar(id: String) = withContext(dispatcher) {
        db.comunicadoQueries.eliminar(id)
        Unit
    }
}

class SqlDelightAlertaDescartadaRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : AlertaDescartadaRepository {
    override fun observarIds(): Flow<Set<String>> =
        db.alertaDescartadaQueries.selectIds().asFlow().mapToList(dispatcher).map { it.toSet() }

    override suspend fun descartar(id: String, usuarioId: String, en: Long) = withContext(dispatcher) {
        db.alertaDescartadaQueries.insertar(id, usuarioId, en)
        Unit
    }
}

class SqlDelightCuentasRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : pe.ecolecta.domain.repository.CuentasRepository {
    override fun observarZonasAsignadas(): Flow<Map<String, String>> =
        db.usuarioZonaQueries.selectTodas().asFlow().mapToList(dispatcher).map { filas -> filas.associate { it.usuario_id to it.zona_id } }

    override suspend fun zonaAsignada(usuarioId: String): String? = withContext(dispatcher) {
        db.usuarioZonaQueries.selectZonaId(usuarioId).executeAsOneOrNull()
    }

    override suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?) = withContext(dispatcher) {
        db.transaction {
            if (zonaId == null) db.usuarioZonaQueries.quitar(usuarioId) else db.usuarioZonaQueries.asignar(usuarioId, zonaId)
            db.proveedorQueries.desvincularUsuario(usuarioId)
            if (proveedorId != null) db.proveedorQueries.vincularUsuario(usuario_id = usuarioId, id = proveedorId)
        }
    }

    override suspend fun existeNombreZona(nombre: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.zonaQueries.existeNombre(nombre, idExcluido).executeAsOne() > 0
    }

    override suspend fun vehiculoEnJornadaAbierta(vehiculoId: String): Boolean = withContext(dispatcher) {
        db.jornadaQueries.selectAbiertaPorVehiculo(vehiculoId).executeAsOneOrNull() != null
    }
}
