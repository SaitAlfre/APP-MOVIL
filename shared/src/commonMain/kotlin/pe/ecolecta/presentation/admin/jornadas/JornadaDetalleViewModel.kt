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
            val jornada = obtenerJornadaUseCase(jornadaId)
            _uiState.update { it.copy(jornada = jornada) }
        }
        viewModelScope.launch {
            observarEntregasDeJornadaUseCase(jornadaId).collect { entregas ->
                _uiState.update { it.copy(cargando = false, entregas = entregas) }
            }
        }
        viewModelScope.launch {
            listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } }
        }
    }
}
