package pe.ecolecta.presentation.admin.entregas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.presentation.cargaSegura

class EntregasViewModel(
    private val observarEntregasUseCase: ObservarEntregasUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(EntregasUiState())
    val uiState: StateFlow<EntregasUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                observarEntregasUseCase().collect { entregas -> _uiState.update { it.copy(cargando = false, entregas = entregas) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las entregas.") } }
        }
        viewModelScope.launch {
            cargaSegura { listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
    }

    fun filtrarPorEstado(estado: SyncState?) {
        _uiState.update { it.copy(filtroSyncState = estado) }
    }
}
