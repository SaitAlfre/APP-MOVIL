package pe.ecolecta.presentation.admin.auditoria

import pe.ecolecta.domain.model.Auditoria

data class AuditoriaUiState(
    val cargando: Boolean = true,
    val registros: List<Auditoria> = emptyList(),
    val error: String? = null,
)
