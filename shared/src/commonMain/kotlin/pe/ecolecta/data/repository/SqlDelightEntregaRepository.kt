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
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository

class SqlDelightEntregaRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : EntregaRepository {

    override fun observarPorJornada(jornadaId: String): Flow<List<Entrega>> =
        db.entregaQueries.selectPorJornada(jornadaId).asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarPorProveedor(proveedorId: String): Flow<List<Entrega>> =
        db.entregaQueries.selectPorProveedor(proveedorId).asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerHistorial(proveedorId: String, limite: Int, desplazamiento: Int): List<Entrega> = withContext(dispatcher) {
        db.entregaQueries.selectPorProveedorPaginado(
            proveedor_id = proveedorId,
            limite = limite.toLong(),
            desplazamiento = desplazamiento.toLong(),
        ).executeAsList().map { it.aDominio() }
    }

    override fun observarConflictos(): Flow<List<Entrega>> =
        db.entregaQueries.selectConflictos().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): Entrega? = withContext(dispatcher) {
        db.entregaQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun filtrar(
        jornadaId: String?,
        proveedorId: String?,
        usuarioId: String?,
        zonaId: String?,
        vehiculoId: String?,
        syncState: SyncState?,
        loteId: String?,
    ): List<Entrega> = withContext(dispatcher) {
        db.entregaQueries.selectConFiltros(
            jornada_id = jornadaId,
            proveedor_id = proveedorId,
            usuario_id = usuarioId,
            zona_id = zonaId,
            vehiculo_id = vehiculoId,
            sync_state = syncState?.name,
            lote_id = loteId,
        ).executeAsList().map { it.aDominio() }
    }

    override fun observarConFiltros(
        jornadaId: String?,
        proveedorId: String?,
        usuarioId: String?,
        zonaId: String?,
        vehiculoId: String?,
        syncState: SyncState?,
        loteId: String?,
    ): Flow<List<Entrega>> = db.entregaQueries.selectConFiltros(
        jornada_id = jornadaId,
        proveedor_id = proveedorId,
        usuario_id = usuarioId,
        zona_id = zonaId,
        vehiculo_id = vehiculoId,
        sync_state = syncState?.name,
        lote_id = loteId,
    ).asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun registrar(entrega: Entrega, auditoria: Auditoria) = withContext(dispatcher) {
        db.entregaQueries.transaction {
            insertarEntregaFila(entrega)
            insertarOutbox(entrega)
            insertarAuditoria(auditoria)
        }
    }

    override suspend fun registrarLote(entregas: List<Entrega>, auditorias: List<Auditoria>) = withContext(dispatcher) {
        db.entregaQueries.transaction {
            entregas.forEach { insertarEntregaFila(it); insertarOutbox(it) }
            auditorias.forEach { insertarAuditoria(it) }
        }
    }

    override suspend fun corregir(id: String, litros: Double, tachos: Int, observaciones: String?, updatedAt: Long, auditoria: Auditoria) =
        withContext(dispatcher) {
            db.entregaQueries.transaction {
                db.entregaQueries.corregir(litros = litros, tachos = tachos.toLong(), observaciones = observaciones, updated_at = updatedAt, id = id)
                insertarAuditoria(auditoria)
            }
        }

    override suspend fun anular(id: String, updatedAt: Long, auditoria: Auditoria) = withContext(dispatcher) {
        db.entregaQueries.transaction {
            db.entregaQueries.anular(updated_at = updatedAt, id = id)
            insertarAuditoria(auditoria)
        }
    }

    override suspend fun resolverConflicto(id: String, litros: Double, tachos: Int, syncState: SyncState, updatedAt: Long, auditoria: Auditoria) =
        withContext(dispatcher) {
            db.entregaQueries.transaction {
                db.entregaQueries.resolverConflicto(
                    litros = litros,
                    tachos = tachos.toLong(),
                    sync_state = syncState.name,
                    updated_at = updatedAt,
                    id = id,
                )
                insertarAuditoria(auditoria)
            }
        }

    override suspend fun sumaLitrosEntreFechas(desde: Long, hasta: Long): Double = withContext(dispatcher) {
        db.entregaQueries.sumaLitrosEntreFechas(desde = desde, hasta = hasta).executeAsOne()
    }

    override suspend fun contarEntregasEntreFechas(desde: Long, hasta: Long): Long = withContext(dispatcher) {
        db.entregaQueries.contarEntregasEntreFechas(desde = desde, hasta = hasta).executeAsOne()
    }

    override suspend fun contarPendientes(): Long = withContext(dispatcher) { db.entregaQueries.contarPendientes().executeAsOne() }

    override suspend fun contarError(): Long = withContext(dispatcher) { db.entregaQueries.contarError().executeAsOne() }

    override suspend fun contarConflicto(): Long = withContext(dispatcher) { db.entregaQueries.contarConflicto().executeAsOne() }

    private fun insertarEntregaFila(entrega: Entrega) {
        db.entregaQueries.insertar(
            id = entrega.id,
            jornada_id = entrega.jornadaId,
            proveedor_id = entrega.proveedorId,
            usuario_id = entrega.usuarioId,
            zona_id = entrega.zonaId,
            vehiculo_id = entrega.vehiculoId,
            litros = entrega.litros,
            tachos = entrega.tachos.toLong(),
            modalidad = entrega.modalidad.name,
            observaciones = entrega.observaciones,
            registrado_en = entrega.registradoEn,
            device_id = entrega.deviceId,
            lote_id = entrega.loteId,
            sync_state = entrega.syncState.name,
            updated_at = entrega.updatedAt,
        )
    }

    private fun insertarOutbox(entrega: Entrega) {
        db.outboxQueries.insertar(
            id = nuevoId(),
            entidad = "entrega",
            entidad_id = entrega.id,
            operacion = "CREAR",
            payload = entrega.id,
            creado_en = entrega.registradoEn,
        )
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
