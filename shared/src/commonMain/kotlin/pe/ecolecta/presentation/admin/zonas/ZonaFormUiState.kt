package pe.ecolecta.presentation.admin.zonas

data class ZonaFormUiState(
    val esEdicion: Boolean = false,
    val nombre: String = "",
    val activo: Boolean = true,
    val cargando: Boolean = false,
    val error: String? = null,
    val guardadoExitoso: Boolean = false,
) {
    val puedeGuardar: Boolean get() = nombre.isNotBlank()
}

sealed interface ZonaFormUiEvent {
    data class NombreCambia(val valor: String) : ZonaFormUiEvent
    data class ActivoCambia(val valor: Boolean) : ZonaFormUiEvent
    data object Guardar : ZonaFormUiEvent
}
