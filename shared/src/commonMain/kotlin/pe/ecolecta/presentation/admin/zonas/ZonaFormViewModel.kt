package pe.ecolecta.presentation.admin.zonas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.zona.ActualizarZonaUseCase
import pe.ecolecta.domain.usecase.zona.CrearZonaUseCase
import pe.ecolecta.domain.usecase.zona.ObtenerZonaUseCase

class ZonaFormViewModel(
    private val id: String?,
    private val obtenerZonaUseCase: ObtenerZonaUseCase,
    private val crearZonaUseCase: CrearZonaUseCase,
    private val actualizarZonaUseCase: ActualizarZonaUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ZonaFormUiState(esEdicion = id != null))
    val uiState: StateFlow<ZonaFormUiState> = _uiState.asStateFlow()

    init {
        if (id != null) {
            viewModelScope.launch {
                val zona = obtenerZonaUseCase(id) ?: return@launch
                _uiState.update { it.copy(nombre = zona.nombre, activo = zona.activo) }
            }
        }
    }

    fun onEvent(evento: ZonaFormUiEvent) {
        when (evento) {
            is ZonaFormUiEvent.NombreCambia -> _uiState.update { it.copy(nombre = evento.valor, error = null) }
            is ZonaFormUiEvent.ActivoCambia -> _uiState.update { it.copy(activo = evento.valor) }
            ZonaFormUiEvent.Guardar -> guardar()
        }
    }

    private fun guardar() {
        val estado = _uiState.value
        if (estado.cargando) return
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }
            val resultado = if (id == null) crearZonaUseCase(estado.nombre, estado.activo).map { }
            else actualizarZonaUseCase(id, estado.nombre, estado.activo)

            resultado.fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, guardadoExitoso = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }
}
