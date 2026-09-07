package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.repository.AuditoriaRepository

class SqlDelightAuditoriaRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : AuditoriaRepository {

    override suspend fun insertar(auditoria: Auditoria) = withContext(dispatcher) {
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
        Unit
    }

    override fun observarTodas(): Flow<List<Auditoria>> =
        db.auditoriaQueries.selectTodas().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun filtrar(usuarioId: String?, entidad: String?, accion: AccionAuditoria?): List<Auditoria> = withContext(dispatcher) {
        db.auditoriaQueries.selectConFiltros(usuario_id = usuarioId, entidad = entidad, accion = accion?.name).executeAsList().map { it.aDominio() }
    }
}
