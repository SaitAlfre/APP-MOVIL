package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.db.Sin_recojo as SinRecojoFila
import pe.ecolecta.domain.acopio.MarcaSinRecojo
import pe.ecolecta.domain.acopio.MotivoSinRecojo
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.SinRecojoRepository

class SqlDelightSinRecojoRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : SinRecojoRepository {

    override fun observarPorZona(zonaId: String, desde: LocalDate, hasta: LocalDate): Flow<List<MarcaSinRecojo>> =
        db.sinRecojoQueries.selectPorZonaEntreFechas(zona_id = zonaId, desde = desde.toString(), hasta = hasta.toString())
            .asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarPorProveedor(proveedorId: String): Flow<List<MarcaSinRecojo>> =
        db.sinRecojoQueries.selectPorProveedor(proveedorId).asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): MarcaSinRecojo? = withContext(dispatcher) {
        db.sinRecojoQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun vigentePara(proveedorId: String, fecha: LocalDate): MarcaSinRecojo? = withContext(dispatcher) {
        db.sinRecojoQueries.selectVigentePara(proveedor_id = proveedorId, fecha = fecha.toString()).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun marcar(marca: MarcaSinRecojo, auditoria: Auditoria) = withContext(dispatcher) {
        db.transaction {
            db.sinRecojoQueries.insertar(
                id = marca.id,
                jornada_id = marca.jornadaId,
                proveedor_id = marca.proveedorId,
                usuario_id = marca.usuarioId,
                zona_id = marca.zonaId,
                fecha = marca.fecha.toString(),
                motivo = marca.motivo.name,
                detalle = marca.detalle,
                registrada_en = marca.registradaEn,
                updated_at = marca.updatedAt,
            )
            insertarAuditoria(auditoria)
        }
    }

    override suspend fun deshacer(id: String, deshechaEn: Long, auditoria: Auditoria) = withContext(dispatcher) {
        db.transaction {
            db.sinRecojoQueries.deshacer(deshecha_en = deshechaEn, id = id)
            insertarAuditoria(auditoria)
        }
    }

    override suspend fun pendientesDeSincronizar(): List<MarcaSinRecojo> = withContext(dispatcher) {
        db.sinRecojoQueries.selectPendientesSync().executeAsList().map { it.aDominio() }
    }

    override suspend fun marcarSincronizada(id: String, updatedAt: Long) = withContext(dispatcher) {
        db.sinRecojoQueries.marcarSincronizada(id = id, updated_at = updatedAt)
        Unit
    }

    override suspend fun registrarFalloSync(id: String, updatedAt: Long, error: String, definitivo: Boolean) = withContext(dispatcher) {
        db.sinRecojoQueries.registrarFalloSync(
            sync_state = if (definitivo) SyncState.ERROR.name else SyncState.PENDING.name,
            sync_error = error,
            id = id,
            updated_at = updatedAt,
        )
        Unit
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

private fun SinRecojoFila.aDominio() = MarcaSinRecojo(
    id = id,
    jornadaId = jornada_id,
    proveedorId = proveedor_id,
    usuarioId = usuario_id,
    zonaId = zona_id,
    fecha = LocalDate.parse(fecha),
    motivo = MotivoSinRecojo.desde(motivo),
    detalle = detalle,
    registradaEn = registrada_en,
    deshecha = deshecha != 0L,
    deshechaEn = deshecha_en,
    syncState = SyncState.entries.firstOrNull { it.name == sync_state } ?: SyncState.ERROR,
    syncError = sync_error,
    intentos = intentos.toInt(),
    updatedAt = updated_at,
)
