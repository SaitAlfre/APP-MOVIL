package pe.ecolecta.presentation.admin.jornadas

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor

data class JornadaDetalleUiState(
    val cargando: Boolean = true,
    val jornada: Jornada? = null,
    val acopiador: String = "",
    val zona: String = "",
    val vehiculo: String = "",
    val entregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val error: String? = null,
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: "Proveedor no disponible"

    /** Las anuladas se listan (con su etiqueta) pero no suman litros ni proveedores. */
    val vigentes: List<Entrega> get() = entregas.filterNot { it.anulada }
    val litros: Double get() = vigentes.sumOf { it.litros }
    val proveedoresAtendidos: Int get() = vigentes.map { it.proveedorId }.distinct().size
    val anuladas: Int get() = entregas.count { it.anulada }
}
