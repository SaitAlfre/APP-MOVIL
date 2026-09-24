package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.repository.TrasladoRepository
import pe.ecolecta.data.local.EntidadCambio
import pe.ecolecta.data.local.marcarCambio

class SqlDelightTrasladoRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : TrasladoRepository {

    override fun observarTodos(): Flow<List<TrasladoZona>> =
        db.trasladoQueries.selectTodos().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarPendientes(): Flow<List<TrasladoZona>> =
        db.trasladoQueries.selectPendientes().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): TrasladoZona? = withContext(dispatcher) {
        db.trasladoQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun insertar(traslado: TrasladoZona) = withContext(dispatcher) {
        db.trasladoQueries.insertar(
            id = traslado.id,
            proveedor_id = traslado.proveedorId,
            zona_origen_id = traslado.zonaOrigenId,
            zona_destino_id = traslado.zonaDestinoId,
            motivo = traslado.motivo,
            creado_en = traslado.creadoEn,
            sync_state = "SYNCED",
        )
        Unit
    }

    override suspend fun autorizar(
        id: String,
        autorizadoPor: String,
        proveedorId: String,
        nuevaZonaId: String,
        updatedAtProveedor: Long,
        auditoria: Auditoria,
    ) = withContext(dispatcher) {
        db.trasladoQueries.transaction {
            db.trasladoQueries.autorizar(autorizado_por = autorizadoPor, id = id)

            val proveedorActual = db.proveedorQueries.selectPorId(proveedorId).executeAsOne()
            db.proveedorQueries.actualizar(
                nombres = proveedorActual.nombres,
                dni = proveedorActual.dni,
                telefono = proveedorActual.telefono,
                direccion = proveedorActual.direccion,
                zona_id = nuevaZonaId,
                tachos = proveedorActual.tachos,
                capacidad_tacho_l = proveedorActual.capacidad_tacho_l,
                updated_at = updatedAtProveedor,
                id = proveedorId,
            )
            db.marcarCambio(EntidadCambio.PROVEEDOR, proveedorId)

            insertarAuditoria(auditoria)
        }
    }

    override suspend fun rechazar(id: String, autorizadoPor: String, auditoria: Auditoria) = withContext(dispatcher) {
        db.trasladoQueries.transaction {
            db.trasladoQueries.rechazar(autorizado_por = autorizadoPor, id = id)
            insertarAuditoria(auditoria)
        }
    }

    private fun insertarAuditoria(auditoria: Auditoria) {
        db.auditoriaQueries.insertar(
            id = auditoria.id,
            entidad = auditoria.entidad,
            entidad_id = auditoria.entidadId,
            accion = auditoria.accion.name,
            valor_antes = auditoria.valorAntes,
            valor_despues = auditoria.valorDespues,
            motivo = auditoria.motivo,
            usuario_id = auditoria.usuarioId,
            ocurrido_en = auditoria.ocurridoEn,
            device_id = auditoria.deviceId,
            sync_state = auditoria.syncState.name,
        )
    }
}
