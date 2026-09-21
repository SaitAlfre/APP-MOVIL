package pe.ecolecta.presentation.admin.auditoria

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auditoria.ListarAuditoriaUseCase
import pe.ecolecta.presentation.cargaSegura

class AuditoriaViewModel(private val listarAuditoriaUseCase: ListarAuditoriaUseCase) : ViewModel() {
    private val _uiState = MutableStateFlow(AuditoriaUiState())
    val uiState: StateFlow<AuditoriaUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                listarAuditoriaUseCase.observarTodas().collect { lista -> _uiState.update { it.copy(cargando = false, registros = lista) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la auditoría.") } }
        }
    }
}
