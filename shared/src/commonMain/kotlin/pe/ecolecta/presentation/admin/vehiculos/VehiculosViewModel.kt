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

class VehiculosViewModel(
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val desactivarVehiculoUseCase: DesactivarVehiculoUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(VehiculosUiState())
    val uiState: StateFlow<VehiculosUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            listarVehiculosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, vehiculos = lista) } }
        }
    }

    fun desactivar(id: String) {
        viewModelScope.launch {
            desactivarVehiculoUseCase(id).onFailure { error -> _uiState.update { it.copy(error = error.message) } }
        }
    }
}
