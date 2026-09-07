package pe.ecolecta.domain.usecase.auditoria

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.repository.AuditoriaRepository

class ListarAuditoriaUseCase(private val auditoriaRepository: AuditoriaRepository) {
    fun observarTodas(): Flow<List<Auditoria>> = auditoriaRepository.observarTodas()

    suspend fun filtrar(usuarioId: String? = null, entidad: String? = null, accion: AccionAuditoria? = null): List<Auditoria> =
        auditoriaRepository.filtrar(usuarioId, entidad, accion)
}
