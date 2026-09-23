package pe.ecolecta.presentation.admin.dashboard

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.debounce
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.EstadoLiquidacion
import pe.ecolecta.domain.usecase.admin.ObservarLiquidacionesUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.dashboard.ObtenerResumenAdminUseCase
import pe.ecolecta.presentation.cargaSegura

@OptIn(kotlinx.coroutines.FlowPreview::class)
class AdminDashboardViewModel(
    private val obtenerResumenAdminUseCase: ObtenerResumenAdminUseCase,
    private val observarLiquidaciones: ObservarLiquidacionesUseCase,
    private val obtenerSesion: ObtenerSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminDashboardUiState())
    val uiState: StateFlow<AdminDashboardUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            obtenerSesion().collect { sesion -> _uiState.update { it.copy(nombreAdmin = sesion?.usuario?.nombres.orEmpty()) } }
        }
        // Las liquidaciones observan todas las entregas: cualquier registro, corrección o
        // resolución vuelve a calcular también las cifras del resumen.
        viewModelScope.launch {
            cargaSegura {
                observarLiquidaciones().debounce(300).collect { semanas ->
                    _uiState.update { it.copy(liquidacionesPorAprobar = semanas.count { s -> s.estado == EstadoLiquidacion.POR_APROBAR }) }
                    cargar()
                }
            }.onFailure { cargar() }
        }
    }

    fun cargar() {
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = it.resumen == null, error = null) }
            cargaSegura { obtenerResumenAdminUseCase() }.fold(
                onSuccess = { resumen -> _uiState.update { it.copy(cargando = false, resumen = resumen) } },
                onFailure = { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar el resumen.") } },
            )
        }
    }
}
