package pe.ecolecta.presentation.admin.jornadas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.presentation.cargaSegura

class JornadaDetalleViewModel(
    private val jornadaId: String,
    private val obtenerJornadaUseCase: ObtenerJornadaUseCase,
    private val observarEntregasDeJornadaUseCase: ObservarEntregasDeJornadaUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(JornadaDetalleUiState())
    val uiState: StateFlow<JornadaDetalleUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura { obtenerJornadaUseCase(jornadaId) }.fold(
                onSuccess = { jornada -> _uiState.update { it.copy(jornada = jornada) } },
                onFailure = { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la jornada.") } },
            )
        }
        viewModelScope.launch {
            cargaSegura {
                observarEntregasDeJornadaUseCase(jornadaId).collect { entregas ->
                    _uiState.update { it.copy(cargando = false, entregas = entregas) }
                }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las entregas.") } }
        }
        viewModelScope.launch {
            cargaSegura {
                listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } }
            }.onFailure { e -> _uiState.update { it.copy(error = e.message ?: "No se pudieron cargar los proveedores.") } }
        }
    }
}
