package pe.ecolecta.presentation.admin.entregas

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState

data class EntregasUiState(
    val cargando: Boolean = true,
    val entregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val filtroSyncState: SyncState? = null,
    /** Solo las anuladas; los filtros de sincronización muestran únicamente entregas vigentes. */
    val soloAnuladas: Boolean = false,
    val error: String? = null,
) {
    val entregasFiltradas: List<Entrega>
        get() = entregas.filter {
            when {
                soloAnuladas -> it.anulada
                filtroSyncState != null -> !it.anulada && it.syncState == filtroSyncState
                else -> true
            }
        }

    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: "Proveedor no disponible"
}
