package pe.ecolecta.presentation.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.usecase.auth.IniciarSesionUseCase
import pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase
import pe.ecolecta.domain.usecase.sync.VincularServidorUseCase

class LoginViewModel(
    private val iniciarSesion: IniciarSesionUseCase,
    private val seleccionarRolUseCase: SeleccionarRolUseCase,
    private val vincularServidor: VincularServidorUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(LoginUiState())
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onEvent(evento: LoginUiEvent) {
        when (evento) {
            is LoginUiEvent.UsernameCambia -> _uiState.update { it.copy(username = evento.valor, error = null) }
            is LoginUiEvent.PinCambia -> _uiState.update { it.copy(pin = evento.valor, error = null) }
            LoginUiEvent.Ingresar -> ingresar()
        }
    }

    private fun ingresar() {
        val estado = _uiState.value
        if (estado.cargando) return
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }
            iniciarSesion(estado.username, estado.pin).fold(
                onSuccess = { inicio ->
                    val usuario = inicio.usuario
                    // En segundo plano: el ingreso offline no espera a la red. Si el panel ya validó la
                    // cuenta (cuenta nueva o PIN cambiado en la web), solo se traen sus datos.
                    if (inicio.enlazado) vincularServidor.yaEnlazado() else vincularServidor(usuario.id, usuario.username, estado.pin)
                    if (usuario.roles.size == 1) {
                        seleccionarRolUseCase(usuario, usuario.roles.first())
                        _uiState.update { it.copy(cargando = false, sesionIniciada = true) }
                    } else {
                        _uiState.update {
                            it.copy(cargando = false, usuarioAutenticadoId = usuario.id, requiereSeleccionRol = true)
                        }
                    }
                },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = mensajeDeError(error)) } },
            )
        }
    }

    private fun mensajeDeError(error: Throwable): String = when (error) {
        is PinInvalidoException.Incorrecto -> "Usuario o PIN incorrecto."
        is PinInvalidoException.Inactivo -> "El usuario está inactivo."
        is PinInvalidoException.Bloqueado -> "Cuenta bloqueada temporalmente por intentos fallidos. Intenta en unos minutos."
        else -> error.message ?: "No se pudo iniciar sesión."
    }
}
