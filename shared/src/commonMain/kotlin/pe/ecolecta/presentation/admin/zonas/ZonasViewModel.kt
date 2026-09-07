package pe.ecolecta.presentation.admin.zonas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.zona.DesactivarZonaUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class ZonasViewModel(
    private val listarZonasUseCase: ListarZonasUseCase,
    private val desactivarZonaUseCase: DesactivarZonaUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ZonasUiState())
    val uiState: StateFlow<ZonasUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            listarZonasUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, zonas = lista) } }
        }
    }

    fun desactivar(id: String) {
        viewModelScope.launch {
            desactivarZonaUseCase(id).onFailure { error -> _uiState.update { it.copy(error = error.message) } }
        }
    }
}
