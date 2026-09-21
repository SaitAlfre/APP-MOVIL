package pe.ecolecta.presentation.proveedor.entregas

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.ResumenEntregas
import pe.ecolecta.domain.model.SyncState

enum class FiltroRangoFecha(val etiqueta: String) {
    TODOS("Todas"),
    HOY("Hoy"),
    AYER("Ayer"),
    ULTIMOS_7("Últimos 7 días"),
    ESTE_MES("Este mes"),
}

enum class RangoResumen(val etiqueta: String) {
    HOY("Hoy"),
    SEMANA("Esta semana"),
    MES("Este mes"),
}

data class MisEntregasUiState(
    val cargando: Boolean = true,
    val entregas: List<Entrega> = emptyList(),
    val hayMasPaginas: Boolean = true,
    val cargandoMas: Boolean = false,
    val filtroRango: FiltroRangoFecha = FiltroRangoFecha.TODOS,
    val filtroEstado: SyncState? = null,
    val rangoResumen: RangoResumen = RangoResumen.HOY,
    val resumen: ResumenEntregas? = null,
    val error: String? = null,
) {
    val usaPaginacion: Boolean get() = filtroRango == FiltroRangoFecha.TODOS && filtroEstado == null
}
