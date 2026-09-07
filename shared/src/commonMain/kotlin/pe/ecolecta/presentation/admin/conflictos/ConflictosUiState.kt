package pe.ecolecta.presentation.admin.conflictos

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor

data class ConflictosUiState(
    val cargando: Boolean = true,
    val conflictos: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: id
}
