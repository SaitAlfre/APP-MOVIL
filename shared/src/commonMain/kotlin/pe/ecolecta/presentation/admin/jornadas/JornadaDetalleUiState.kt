package pe.ecolecta.presentation.admin.jornadas

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor

data class JornadaDetalleUiState(
    val cargando: Boolean = true,
    val jornada: Jornada? = null,
    val entregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val error: String? = null,
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: id
}
