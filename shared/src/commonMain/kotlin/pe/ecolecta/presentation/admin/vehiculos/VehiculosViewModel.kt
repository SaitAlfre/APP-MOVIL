package pe.ecolecta.presentation.admin.vehiculos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.vehiculo.DesactivarVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.presentation.cargaSegura

class VehiculosViewModel(
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val desactivarVehiculoUseCase: DesactivarVehiculoUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(VehiculosUiState())
    val uiState: StateFlow<VehiculosUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura { listarVehiculosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, vehiculos = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar los vehículos.") } }
        }
    }

    fun desactivar(id: String) {
        viewModelScope.launch {
            desactivarVehiculoUseCase(id).onFailure { error -> _uiState.update { it.copy(error = error.message) } }
        }
    }
}
