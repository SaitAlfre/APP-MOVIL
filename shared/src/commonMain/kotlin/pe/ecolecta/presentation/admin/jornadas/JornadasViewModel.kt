package pe.ecolecta.presentation.admin.jornadas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.jornada.ObservarJornadasUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

class JornadasViewModel(
    private val observarJornadasUseCase: ObservarJornadasUseCase,
    private val listarUsuariosUseCase: ListarUsuariosUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(JornadasUiState())
    val uiState: StateFlow<JornadasUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura { observarJornadasUseCase().collect { jornadas -> _uiState.update { it.copy(cargando = false, jornadas = jornadas) } } }
                .onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las jornadas.") } }
        }
        viewModelScope.launch {
            cargaSegura { listarUsuariosUseCase().collect { lista -> _uiState.update { it.copy(usuarios = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
        viewModelScope.launch {
            cargaSegura { listarZonasUseCase().collect { lista -> _uiState.update { it.copy(zonas = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
        viewModelScope.launch {
            cargaSegura { listarVehiculosUseCase().collect { lista -> _uiState.update { it.copy(vehiculos = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
    }

    fun filtrarPorZona(zonaId: String?) {
        _uiState.update { it.copy(filtroZonaId = zonaId) }
    }
}
