package pe.ecolecta.presentation.acopiador.registro

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.ModalidadEntrega
import pe.ecolecta.domain.model.Proveedor

data class EntregaExistente(val entrega: Entrega, val litrosNuevos: Double, val tachosNuevos: Int)

data class RegistroEntregaUiState(
    val proveedores: List<Proveedor> = emptyList(),
    val proveedorId: String = "",
    val zonaNombre: String = "",
    val entregadoHoyDelSeleccionado: Boolean = false,
    val litros: String = "",
    val tachos: String = "1",
    val modalidad: ModalidadEntrega = ModalidadEntrega.MEDIANTE_ACOPIADOR,
    val observaciones: String = "",
    val cargando: Boolean = false,
    val error: String? = null,
    val advertenciaDesviacion: Boolean = false,
    val guardadoExitoso: Boolean = false,
    val entregaDuplicada: EntregaExistente? = null,
) {
    val puedeGuardar: Boolean
        get() = proveedorId.isNotBlank() && (litros.toDoubleOrNull() ?: 0.0) > 0.0 && (tachos.toIntOrNull() ?: 0) > 0

    val proveedorSeleccionado: Proveedor?
        get() = proveedores.firstOrNull { it.id == proveedorId }

    fun nombreProveedorSeleccionado(): String =
        proveedores.firstOrNull { it.id == proveedorId }?.let { "${it.codigo} - ${it.nombres}" } ?: ""
}
