package pe.ecolecta.presentation.admin.usuarios

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.usecase.usuario.ActualizarUsuarioCompletoUseCase
import pe.ecolecta.domain.usecase.usuario.CrearUsuarioUseCase
import pe.ecolecta.domain.usecase.usuario.ObtenerUsuarioUseCase
import pe.ecolecta.presentation.cargaSegura

class UsuarioFormViewModel(
    private val id: String?,
    private val obtenerUsuarioUseCase: ObtenerUsuarioUseCase,
    private val crearUsuarioUseCase: CrearUsuarioUseCase,
    private val actualizarUsuarioCompletoUseCase: ActualizarUsuarioCompletoUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(UsuarioFormUiState(esEdicion = id != null))
    val uiState: StateFlow<UsuarioFormUiState> = _uiState.asStateFlow()

    private var rolesOriginales: Set<Rol> = emptySet()

    init {
        if (id != null) {
            viewModelScope.launch {
                cargaSegura { obtenerUsuarioUseCase(id) }.fold(
                    onSuccess = { usuario ->
                        if (usuario != null) {
                            rolesOriginales = usuario.roles.toSet()
                            _uiState.update {
                                it.copy(
                                    username = usuario.username,
                                    nombres = usuario.nombres,
                                    dni = usuario.dni,
                                    roles = usuario.roles.toSet(),
                                    activo = usuario.activo,
                                )
                            }
                        }
                    },
                    onFailure = { e -> _uiState.update { it.copy(error = e.message ?: "No se pudo cargar el usuario.") } },
                )
            }
        }
    }

    fun onEvent(evento: UsuarioFormUiEvent) {
        when (evento) {
            is UsuarioFormUiEvent.NombresCambia -> _uiState.update { it.copy(nombres = evento.valor, error = null) }
            is UsuarioFormUiEvent.UsernameCambia -> _uiState.update { it.copy(username = evento.valor, error = null) }
            is UsuarioFormUiEvent.DniCambia -> _uiState.update { it.copy(dni = evento.valor, error = null) }
            is UsuarioFormUiEvent.PinCambia -> _uiState.update { it.copy(pin = evento.valor, error = null) }
            is UsuarioFormUiEvent.ConfirmacionPinCambia -> _uiState.update { it.copy(confirmacionPin = evento.valor, error = null) }
            is UsuarioFormUiEvent.RolToggle -> _uiState.update {
                val roles = if (evento.rol in it.roles) it.roles - evento.rol else it.roles + evento.rol
                it.copy(roles = roles)
            }
            is UsuarioFormUiEvent.ActivoCambia -> _uiState.update { it.copy(activo = evento.valor) }
            is UsuarioFormUiEvent.CambiarPinToggle -> _uiState.update { it.copy(cambiarPin = evento.valor) }
            UsuarioFormUiEvent.Guardar -> guardar()
        }
    }

    private fun guardar() {
        val estado = _uiState.value
        if (estado.cargando) return
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }

            val resultado = if (!estado.esEdicion) {
                crearUsuarioUseCase(
                    username = estado.username,
                    nombres = estado.nombres,
                    dni = estado.dni,
                    pin = estado.pin,
                    confirmacionPin = estado.confirmacionPin,
                    roles = estado.roles.toList(),
                    activo = estado.activo,
                ).map { }
            } else {
                actualizarUsuarioCompletoUseCase(
                    id = id!!,
                    nombres = estado.nombres,
                    dni = estado.dni,
                    activo = estado.activo,
                    rolesFinales = estado.roles,
                    rolesOriginales = rolesOriginales,
                    nuevoPin = if (estado.cambiarPin) estado.pin else null,
                    confirmacionPin = if (estado.cambiarPin) estado.confirmacionPin else null,
                )
            }

            resultado.fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, guardadoExitoso = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message ?: "No se pudo guardar.") } },
            )
        }
    }
}
