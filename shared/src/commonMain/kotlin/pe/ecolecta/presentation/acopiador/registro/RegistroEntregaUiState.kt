package pe.ecolecta.presentation.acopiador.registro

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor

data class EntregaExistente(val entrega: Entrega, val litrosNuevos: Double, val tachosNuevos: Int)

data class RegistroEntregaUiState(
    val proveedores: List<Proveedor> = emptyList(),
    val proveedorId: String = "",
    val litros: String = "",
    val tachos: String = "1",
    val observaciones: String = "",
    val cargando: Boolean = false,
    val error: String? = null,
    val advertenciaDesviacion: Boolean = false,
    val guardadoExitoso: Boolean = false,
    val entregaDuplicada: EntregaExistente? = null,
) {
    val puedeGuardar: Boolean
        get() = proveedorId.isNotBlank() && (litros.toDoubleOrNull() ?: 0.0) > 0.0 && (tachos.toIntOrNull() ?: 0) > 0

    fun nombreProveedorSeleccionado(): String =
        proveedores.firstOrNull { it.id == proveedorId }?.let { "${it.codigo} - ${it.nombres}" } ?: ""
}
