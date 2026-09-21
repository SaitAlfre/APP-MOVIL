package pe.ecolecta.presentation.proveedor.perfil

import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.ResumenSyncProveedor

data class PerfilProveedorUiState(
    val cargando: Boolean = true,
    val proveedor: Proveedor? = null,
    val nombreZona: String = "",
    val resumenSync: ResumenSyncProveedor = ResumenSyncProveedor(0, 0, 0, 0),
    val mensajeSincronizar: String? = null,
    val error: String? = null,
)
