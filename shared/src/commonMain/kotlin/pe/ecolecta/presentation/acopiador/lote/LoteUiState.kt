package pe.ecolecta.presentation.acopiador.lote

import pe.ecolecta.domain.model.Proveedor

data class FilaLote(val proveedorId: String = "", val litros: String = "", val tachos: String = "1")

data class LoteUiState(
    val proveedores: List<Proveedor> = emptyList(),
    val filas: List<FilaLote> = listOf(FilaLote()),
    val cargando: Boolean = false,
    val error: String? = null,
    val guardadoExitoso: Boolean = false,
) {
    val puedeGuardar: Boolean
        get() = filas.isNotEmpty() && filas.all {
            it.proveedorId.isNotBlank() && (it.litros.toDoubleOrNull() ?: 0.0) > 0.0 && (it.tachos.toIntOrNull() ?: 0) > 0
        }

    fun nombreProveedor(id: String): String =
        proveedores.firstOrNull { it.id == id }?.let { "${it.codigo} - ${it.nombres}" } ?: "Elegir proveedor"
}
