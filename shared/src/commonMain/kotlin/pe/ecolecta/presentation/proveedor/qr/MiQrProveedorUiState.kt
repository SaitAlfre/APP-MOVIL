package pe.ecolecta.presentation.proveedor.qr

import pe.ecolecta.domain.model.Proveedor

data class MiQrProveedorUiState(
    val cargando: Boolean = true,
    val proveedor: Proveedor? = null,
    val nombreZona: String = "",
    val contenidoQr: String = "",
)
