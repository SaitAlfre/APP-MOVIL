package pe.ecolecta.presentation.acopiador.onboarding

import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona

data class SeleccionZonaVehiculoUiState(
    val zonas: List<Zona> = emptyList(),
    val vehiculos: List<Vehiculo> = emptyList(),
    val zonaId: String? = null,
    val vehiculoId: String? = null,
    val cargando: Boolean = false,
    val error: String? = null,
    val jornadaAbierta: Boolean = false,
) {
    val puedeContinuar: Boolean get() = zonaId != null && vehiculoId != null
}
