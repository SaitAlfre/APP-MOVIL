package pe.ecolecta.presentation.admin.auditoria

import pe.ecolecta.domain.model.Auditoria

/** Un registro de auditoría con su autor y el registro afectado ya descritos en palabras. */
data class RegistroAuditoria(val auditoria: Auditoria, val autor: String, val sobre: String)

data class AuditoriaUiState(
    val cargando: Boolean = true,
    val registros: List<RegistroAuditoria> = emptyList(),
    val error: String? = null,
)
