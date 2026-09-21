package pe.ecolecta.presentation.admin.proveedores

import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Zona

data class ProveedoresUiState(
    val cargando: Boolean = true,
    val proveedores: List<Proveedor> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val filtroTexto: String = "",
    val filtroZonaId: String? = null,
    val filtroEstado: EstadoProveedor? = null,
    val error: String? = null,
) {
    val proveedoresFiltrados: List<Proveedor>
        get() = proveedores.filter { p ->
            (filtroTexto.isBlank() ||
                p.codigo.contains(filtroTexto, ignoreCase = true) ||
                p.nombres.contains(filtroTexto, ignoreCase = true) ||
                p.dni.contains(filtroTexto)) &&
                (filtroZonaId == null || p.zonaId == filtroZonaId) &&
                (filtroEstado == null || p.estado == filtroEstado)
        }

    fun nombreZona(id: String): String = zonas.firstOrNull { it.id == id }?.nombre ?: id
}

sealed interface ProveedoresUiEvent {
    data class FiltroTextoCambia(val valor: String) : ProveedoresUiEvent
    data class FiltroZonaCambia(val valor: String?) : ProveedoresUiEvent
    data class FiltroEstadoCambia(val valor: EstadoProveedor?) : ProveedoresUiEvent
    data class Retirar(val id: String) : ProveedoresUiEvent
}
