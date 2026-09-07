package pe.ecolecta.presentation.admin.vehiculos

import pe.ecolecta.domain.model.Vehiculo

data class VehiculosUiState(
    val cargando: Boolean = true,
    val vehiculos: List<Vehiculo> = emptyList(),
    val error: String? = null,
)
