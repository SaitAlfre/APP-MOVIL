package pe.ecolecta.presentation.acopiador.onboarding

import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.presentation.acopiador.ciclo.CicloAcopio

data class SeleccionZonaVehiculoUiState(
    val zonas: List<Zona> = emptyList(),
    val vehiculos: List<Vehiculo> = emptyList(),
    val zonaId: String? = null,
    val vehiculoId: String? = null,
    val cargando: Boolean = false,
    val error: String? = null,
    val jornadaAbierta: Boolean = false,
    /** Ventana del ciclo en curso. Proviene de un placeholder; ver [CicloAcopio]. */
    val ciclo: CicloAcopio? = null,
) {
    val puedeContinuar: Boolean get() = zonaId != null && vehiculoId != null
}
