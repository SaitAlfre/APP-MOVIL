package pe.ecolecta.presentation.admin.vehiculos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.vehiculo.ActualizarVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.CrearVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.ObtenerVehiculoUseCase

class VehiculoFormViewModel(
    private val id: String?,
    private val obtenerVehiculoUseCase: ObtenerVehiculoUseCase,
    private val crearVehiculoUseCase: CrearVehiculoUseCase,
    private val actualizarVehiculoUseCase: ActualizarVehiculoUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(VehiculoFormUiState(esEdicion = id != null))
    val uiState: StateFlow<VehiculoFormUiState> = _uiState.asStateFlow()

    init {
        if (id != null) {
            viewModelScope.launch {
                val vehiculo = obtenerVehiculoUseCase(id) ?: return@launch
                _uiState.update { it.copy(nombre = vehiculo.nombre, placa = vehiculo.placa, activo = vehiculo.activo) }
            }
        }
    }

    fun onEvent(evento: VehiculoFormUiEvent) {
        when (evento) {
            is VehiculoFormUiEvent.NombreCambia -> _uiState.update { it.copy(nombre = evento.valor, error = null) }
            is VehiculoFormUiEvent.PlacaCambia -> _uiState.update { it.copy(placa = evento.valor, error = null) }
            is VehiculoFormUiEvent.ActivoCambia -> _uiState.update { it.copy(activo = evento.valor) }
            VehiculoFormUiEvent.Guardar -> guardar()
        }
    }

    private fun guardar() {
        val estado = _uiState.value
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }
            val resultado = if (id == null) crearVehiculoUseCase(estado.nombre, estado.placa, estado.activo).map { }
            else actualizarVehiculoUseCase(id, estado.nombre, estado.placa, estado.activo)

            resultado.fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, guardadoExitoso = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }
}
