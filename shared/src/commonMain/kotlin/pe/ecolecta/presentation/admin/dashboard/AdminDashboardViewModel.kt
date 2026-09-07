package pe.ecolecta.presentation.admin.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.dashboard.ObtenerResumenAdminUseCase

class AdminDashboardViewModel(
    private val obtenerResumenAdminUseCase: ObtenerResumenAdminUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminDashboardUiState())
    val uiState: StateFlow<AdminDashboardUiState> = _uiState.asStateFlow()

    init {
        cargar()
    }

    fun cargar() {
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true) }
            val resumen = obtenerResumenAdminUseCase()
            _uiState.update { it.copy(cargando = false, resumen = resumen) }
        }
    }
}
