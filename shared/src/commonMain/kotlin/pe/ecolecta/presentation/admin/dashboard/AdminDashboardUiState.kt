package pe.ecolecta.presentation.admin.dashboard

import pe.ecolecta.domain.usecase.dashboard.ResumenAdmin

data class AdminDashboardUiState(
    val cargando: Boolean = true,
    val resumen: ResumenAdmin? = null,
    val nombreAdmin: String = "",
    val liquidacionesPorAprobar: Int = 0,
    val error: String? = null,
    /** Cambios hechos aquí que aún no llegan al panel web, o que el panel rechazó. */
    val avisoCambios: String? = null,
    val avisoCambiosEsError: Boolean = false,
)
