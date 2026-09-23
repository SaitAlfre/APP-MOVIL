package pe.ecolecta.presentation.acopiador.entregas

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor

data class EntregasDelDiaUiState(
    val cargando: Boolean = true,
    val entregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val jornadaAbierta: Boolean = false,
) {
    val ordenadas: List<Entrega> get() = entregas.sortedByDescending(Entrega::registradoEn)
    val totalLitros: Double get() = entregas.filterNot(Entrega::anulada).sumOf(Entrega::litros)
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: id
}
