package pe.ecolecta.presentation.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase
import pe.ecolecta.domain.usecase.usuario.ObtenerUsuarioUseCase
import pe.ecolecta.presentation.cargaSegura

class SeleccionRolViewModel(
    private val usuarioId: String,
    private val obtenerUsuarioUseCase: ObtenerUsuarioUseCase,
    private val seleccionarRolUseCase: SeleccionarRolUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(SeleccionRolUiState())
    val uiState: StateFlow<SeleccionRolUiState> = _uiState.asStateFlow()

    private var usuario: Usuario? = null

    init {
        viewModelScope.launch {
            cargaSegura { obtenerUsuarioUseCase(usuarioId) }.fold(
                onSuccess = { encontrado ->
                    usuario = encontrado
                    _uiState.update { it.copy(cargando = false, roles = encontrado?.roles.orEmpty()) }
                },
                onFailure = { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar tu usuario.") } },
            )
        }
    }

    fun seleccionar(rol: Rol) {
        val usuarioActual = usuario ?: return
        if (_uiState.value.seleccionando) return
        _uiState.update { it.copy(seleccionando = true, error = null) }
        viewModelScope.launch {
            seleccionarRolUseCase(usuarioActual, rol).fold(
                onSuccess = { _uiState.update { it.copy(seleccionando = false, rolSeleccionado = rol) } },
                onFailure = { error ->
                    _uiState.update { it.copy(seleccionando = false, error = error.message ?: "No se pudo seleccionar el rol.") }
                },
            )
        }
    }
}
