package pe.ecolecta.presentation.admin.zonas

import pe.ecolecta.domain.model.Zona

data class ZonasUiState(
    val cargando: Boolean = true,
    val zonas: List<Zona> = emptyList(),
    val error: String? = null,
)
