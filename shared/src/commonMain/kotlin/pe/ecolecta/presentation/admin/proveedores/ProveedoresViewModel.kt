package pe.ecolecta.presentation.admin.proveedores

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.proveedor.RetirarProveedorUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class ProveedoresViewModel(
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val retirarProveedorUseCase: RetirarProveedorUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProveedoresUiState())
    val uiState: StateFlow<ProveedoresUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            combine(listarProveedoresUseCase(), listarZonasUseCase()) { proveedores, zonas -> proveedores to zonas }
                .collect { (proveedores, zonas) ->
                    _uiState.update { it.copy(cargando = false, proveedores = proveedores, zonas = zonas) }
                }
        }
    }

    fun onEvent(evento: ProveedoresUiEvent) {
        when (evento) {
            is ProveedoresUiEvent.FiltroTextoCambia -> _uiState.update { it.copy(filtroTexto = evento.valor) }
            is ProveedoresUiEvent.FiltroZonaCambia -> _uiState.update { it.copy(filtroZonaId = evento.valor) }
            is ProveedoresUiEvent.FiltroEstadoCambia -> _uiState.update { it.copy(filtroEstado = evento.valor) }
            is ProveedoresUiEvent.Retirar -> viewModelScope.launch { retirarProveedorUseCase(evento.id) }
        }
    }
}
