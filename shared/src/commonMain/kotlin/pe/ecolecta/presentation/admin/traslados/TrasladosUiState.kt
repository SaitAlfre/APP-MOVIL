package pe.ecolecta.presentation.admin.traslados

import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.model.Zona

sealed interface DialogoTraslado {
    data object Crear : DialogoTraslado
    data class Autorizar(val id: String) : DialogoTraslado
    data class Rechazar(val id: String) : DialogoTraslado
}

data class TrasladosUiState(
    val cargando: Boolean = true,
    val traslados: List<TrasladoZona> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val dialogo: DialogoTraslado? = null,
    val procesando: Boolean = false,
    val errorDialogo: String? = null,
    val mensaje: String? = null,
    val error: String? = null,
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.let { "${it.codigo} · ${it.nombres}" } ?: "Proveedor no disponible"
    fun nombreZona(id: String): String = zonas.firstOrNull { it.id == id }?.nombre ?: "Zona no disponible"
    fun traslado(id: String): TrasladoZona? = traslados.firstOrNull { it.id == id }
}
