package pe.ecolecta.presentation.admin.entregas

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState

data class EntregasUiState(
    val cargando: Boolean = true,
    val entregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val filtroSyncState: SyncState? = null,
    val error: String? = null,
) {
    val entregasFiltradas: List<Entrega>
        get() = entregas.filter { filtroSyncState == null || it.syncState == filtroSyncState }

    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: id
}
