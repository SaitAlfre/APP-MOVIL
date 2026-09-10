package pe.ecolecta.presentation.proveedor.ruta

import pe.ecolecta.domain.model.EstadoRutaAcopio

data class MiRutaAcopioUiState(
    val cargando: Boolean = true,
    val estado: EstadoRutaAcopio = EstadoRutaAcopio.SinRutaAsignada,
    /** Se refresca con un ticker para que "hace X min" no se quede congelado mientras la pantalla está abierta. */
    val ahoraMs: Long = 0L,
)
