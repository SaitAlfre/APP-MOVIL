package pe.ecolecta.presentation.acopiador.onboarding

import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.acopio.CicloAcopio

data class SeleccionZonaVehiculoUiState(
    val zonas: List<Zona> = emptyList(),
    val vehiculos: List<Vehiculo> = emptyList(),
    val zonaId: String? = null,
    val vehiculoId: String? = null,
    val cargando: Boolean = false,
    val error: String? = null,
    val jornadaAbierta: Boolean = false,
    /** Ventana del ciclo de 6 días en curso (fecha de Perú); ver [CicloAcopio]. */
    val ciclo: CicloAcopio? = null,
    /** Aún no llegó la primera lista de zonas/vehículos: no se muestra un formulario vacío. */
    val zonasCargadas: Boolean = false,
    val vehiculosCargados: Boolean = false,
    /** Zona asignada por el administrador a esta cuenta (puede no estar activa). */
    val zonaAsignadaId: String? = null,
    /** El acopiador tocó una zona: la sugerencia que llegue después no debe pisar su elección. */
    val zonaElegidaManualmente: Boolean = false,
    val mostrarConfirmacionCierreSesion: Boolean = false,
    val cerrandoSesion: Boolean = false,
) {
    val cargandoCatalogo: Boolean get() = !zonasCargadas || !vehiculosCargados

    val sinZonasActivas: Boolean get() = zonasCargadas && zonas.isEmpty()

    val sinVehiculosActivos: Boolean get() = vehiculosCargados && vehiculos.isEmpty()

    /** La zona asignada existe pero está desactivada (el acopiador aún puede elegir otra activa). */
    val zonaAsignadaInactiva: Boolean
        get() = zonasCargadas && zonaAsignadaId != null && zonas.none { it.id == zonaAsignadaId }

    val puedeContinuar: Boolean get() = zonaId != null && vehiculoId != null
}
