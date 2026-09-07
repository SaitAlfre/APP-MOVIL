package pe.ecolecta.presentation.admin.vehiculos

data class VehiculoFormUiState(
    val esEdicion: Boolean = false,
    val nombre: String = "",
    val placa: String = "",
    val activo: Boolean = true,
    val cargando: Boolean = false,
    val error: String? = null,
    val guardadoExitoso: Boolean = false,
) {
    val puedeGuardar: Boolean get() = nombre.isNotBlank() && placa.isNotBlank()
}

sealed interface VehiculoFormUiEvent {
    data class NombreCambia(val valor: String) : VehiculoFormUiEvent
    data class PlacaCambia(val valor: String) : VehiculoFormUiEvent
    data class ActivoCambia(val valor: Boolean) : VehiculoFormUiEvent
    data object Guardar : VehiculoFormUiEvent
}
