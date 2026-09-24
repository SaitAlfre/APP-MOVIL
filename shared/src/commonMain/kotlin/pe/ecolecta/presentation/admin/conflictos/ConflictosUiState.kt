package pe.ecolecta.presentation.admin.conflictos

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.OrigenValorConflicto
import pe.ecolecta.domain.model.Proveedor

data class ConflictosUiState(
    val cargando: Boolean = true,
    val conflictos: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    /** Entrega y valor elegido cuyo motivo se está pidiendo. */
    val resolucion: Pair<String, OrigenValorConflicto>? = null,
    val procesando: Boolean = false,
    val errorDialogo: String? = null,
    val mensaje: String? = null,
    val error: String? = null,
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: "Proveedor no disponible"
}
