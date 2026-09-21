package pe.ecolecta.presentation.admin.dashboard

import pe.ecolecta.domain.usecase.dashboard.ResumenAdmin

data class AdminDashboardUiState(
    val cargando: Boolean = true,
    val resumen: ResumenAdmin? = null,
    val error: String? = null,
)
