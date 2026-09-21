package pe.ecolecta.presentation.admin.usuarios

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.usuario.DesactivarUsuarioUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.presentation.cargaSegura

class UsuariosViewModel(
    private val listarUsuariosUseCase: ListarUsuariosUseCase,
    private val desactivarUsuarioUseCase: DesactivarUsuarioUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(UsuariosUiState())
    val uiState: StateFlow<UsuariosUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura { listarUsuariosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, usuarios = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar los usuarios.") } }
        }
    }

    fun onEvent(evento: UsuariosUiEvent) {
        when (evento) {
            is UsuariosUiEvent.FiltroTextoCambia -> _uiState.update { it.copy(filtroTexto = evento.valor) }
            is UsuariosUiEvent.FiltroRolCambia -> _uiState.update { it.copy(filtroRol = evento.valor) }
            is UsuariosUiEvent.SoloActivosCambia -> _uiState.update { it.copy(soloActivos = evento.valor) }
            is UsuariosUiEvent.Desactivar -> viewModelScope.launch { desactivarUsuarioUseCase(evento.id) }
        }
    }
}
