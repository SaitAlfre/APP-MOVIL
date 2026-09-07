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
            val encontrado = obtenerUsuarioUseCase(usuarioId)
            usuario = encontrado
            _uiState.update { it.copy(cargando = false, roles = encontrado?.roles.orEmpty()) }
        }
    }

    fun seleccionar(rol: Rol) {
        val usuarioActual = usuario ?: return
        viewModelScope.launch {
            seleccionarRolUseCase(usuarioActual, rol)
            _uiState.update { it.copy(rolSeleccionado = rol) }
        }
    }
}
