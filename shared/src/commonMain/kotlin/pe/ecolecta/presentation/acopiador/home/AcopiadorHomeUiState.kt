package pe.ecolecta.presentation.acopiador.home

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor

data class AcopiadorHomeUiState(
    val cargando: Boolean = true,
    val litrosHoy: Double = 0.0,
    val entregasHoy: Int = 0,
    val pendientesSync: Int = 0,
    val ultimasEntregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: id
}
