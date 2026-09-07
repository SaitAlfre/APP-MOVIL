package pe.ecolecta.presentation.auth

data class LoginUiState(
    val username: String = "",
    val pin: String = "",
    val cargando: Boolean = false,
    val error: String? = null,
    val usuarioAutenticadoId: String? = null,
    val requiereSeleccionRol: Boolean = false,
    val sesionIniciada: Boolean = false,
)

sealed interface LoginUiEvent {
    data class UsernameCambia(val valor: String) : LoginUiEvent
    data class PinCambia(val valor: String) : LoginUiEvent
    data object Ingresar : LoginUiEvent
}
