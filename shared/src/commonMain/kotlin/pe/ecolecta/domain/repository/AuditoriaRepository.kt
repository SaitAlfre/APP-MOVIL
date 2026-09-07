package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria

/** La auditoría es inmutable: este repositorio solo expone inserción y consulta (§15), nunca update/delete. */
interface AuditoriaRepository {
    suspend fun insertar(auditoria: Auditoria)
    fun observarTodas(): Flow<List<Auditoria>>
    suspend fun filtrar(usuarioId: String? = null, entidad: String? = null, accion: AccionAuditoria? = null): List<Auditoria>
}
