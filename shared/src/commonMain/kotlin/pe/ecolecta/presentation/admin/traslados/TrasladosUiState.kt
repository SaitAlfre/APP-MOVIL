package pe.ecolecta.presentation.admin.traslados

import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.model.Zona

data class TrasladosUiState(
    val cargando: Boolean = true,
    val traslados: List<TrasladoZona> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val mostrarDialogoCrear: Boolean = false,
    val error: String? = null,
) {
    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.let { "${it.codigo} - ${it.nombres}" } ?: id
    fun nombreZona(id: String): String = zonas.firstOrNull { it.id == id }?.nombre ?: id
}
