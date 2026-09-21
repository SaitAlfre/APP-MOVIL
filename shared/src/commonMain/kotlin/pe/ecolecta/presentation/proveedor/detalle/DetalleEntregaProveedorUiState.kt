package pe.ecolecta.presentation.proveedor.detalle

import pe.ecolecta.domain.model.Entrega

data class DetalleEntregaProveedorUiState(
    val cargando: Boolean = true,
    val entrega: Entrega? = null,
    val nombreZona: String = "",
    val nombreVehiculo: String = "",
    val nombreAcopiador: String = "",
    val noEncontrada: Boolean = false,
    val error: String? = null,
)
