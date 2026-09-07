package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.repository.AuditoriaRepository

class FakeAuditoriaRepository : AuditoriaRepository {
    private val registros = MutableStateFlow<List<Auditoria>>(emptyList())
    val insertados: List<Auditoria> get() = registros.value

    override suspend fun insertar(auditoria: Auditoria) {
        registros.value = registros.value + auditoria
    }

    override fun observarTodas(): Flow<List<Auditoria>> = registros.asStateFlow()

    override suspend fun filtrar(usuarioId: String?, entidad: String?, accion: AccionAuditoria?): List<Auditoria> =
        registros.value.filter {
            (usuarioId == null || it.usuarioId == usuarioId) &&
                (entidad == null || it.entidad == entidad) &&
                (accion == null || it.accion == accion)
        }
}
