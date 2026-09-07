package pe.ecolecta.presentation.admin.usuarios

import pe.ecolecta.domain.model.Rol

data class UsuarioFormUiState(
    val esEdicion: Boolean = false,
    val username: String = "",
    val nombres: String = "",
    val dni: String = "",
    val pin: String = "",
    val confirmacionPin: String = "",
    val roles: Set<Rol> = emptySet(),
    val activo: Boolean = true,
    val cambiarPin: Boolean = false,
    val cargando: Boolean = false,
    val error: String? = null,
    val guardadoExitoso: Boolean = false,
) {
    val puedeGuardar: Boolean
        get() = username.isNotBlank() && nombres.isNotBlank() && dni.isNotBlank() && roles.isNotEmpty() &&
            (if (esEdicion) !cambiarPin || (pin.length == 4 && confirmacionPin.length == 4) else pin.length == 4 && confirmacionPin.length == 4)
}

sealed interface UsuarioFormUiEvent {
    data class NombresCambia(val valor: String) : UsuarioFormUiEvent
    data class UsernameCambia(val valor: String) : UsuarioFormUiEvent
    data class DniCambia(val valor: String) : UsuarioFormUiEvent
    data class PinCambia(val valor: String) : UsuarioFormUiEvent
    data class ConfirmacionPinCambia(val valor: String) : UsuarioFormUiEvent
    data class RolToggle(val rol: Rol) : UsuarioFormUiEvent
    data class ActivoCambia(val valor: Boolean) : UsuarioFormUiEvent
    data class CambiarPinToggle(val valor: Boolean) : UsuarioFormUiEvent
    data object Guardar : UsuarioFormUiEvent
}
