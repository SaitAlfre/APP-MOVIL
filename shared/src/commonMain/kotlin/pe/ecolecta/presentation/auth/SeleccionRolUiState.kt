package pe.ecolecta.presentation.auth

import pe.ecolecta.domain.model.Rol

data class SeleccionRolUiState(
    val cargando: Boolean = true,
    val roles: List<Rol> = emptyList(),
    val rolSeleccionado: Rol? = null,
)
