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

    /** Lo que el acopiador contrasta contra lo que lleva en el vehículo antes de guardar. */
    val litrosTotales: Double get() = filas.sumOf { it.litros.toDoubleOrNull() ?: 0.0 }
    val tachosTotales: Int get() = filas.sumOf { it.tachos.toIntOrNull() ?: 0 }

    fun nombreProveedor(id: String): String =
        proveedores.firstOrNull { it.id == id }?.nombres ?: "Elegir proveedor"

    /** "P-001 · 12 tachos", o null para una fila a la que todavía no se le eligió proveedor. */
    fun detalleProveedor(id: String): String? =
        proveedores.firstOrNull { it.id == id }?.let { "${it.codigo} · ${it.tachos} tachos" }
}
