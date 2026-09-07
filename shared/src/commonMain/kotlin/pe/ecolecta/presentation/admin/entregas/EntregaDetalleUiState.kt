package pe.ecolecta.presentation.admin.entregas

import pe.ecolecta.domain.model.Entrega

data class EntregaDetalleUiState(
    val cargando: Boolean = true,
    val entrega: Entrega? = null,
    val proveedorNombre: String = "",
    val error: String? = null,
)
