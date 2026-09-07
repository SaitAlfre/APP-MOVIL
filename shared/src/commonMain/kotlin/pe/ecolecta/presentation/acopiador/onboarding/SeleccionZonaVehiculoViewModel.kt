package pe.ecolecta.presentation.acopiador.onboarding

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class SeleccionZonaVehiculoViewModel(
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val abrirJornadaUseCase: AbrirJornadaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(SeleccionZonaVehiculoUiState())
    val uiState: StateFlow<SeleccionZonaVehiculoUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            listarZonasUseCase(soloActivas = true).collect { lista ->
                _uiState.update { it.copy(zonas = lista, zonaId = it.zonaId ?: lista.firstOrNull()?.id) }
            }
        }
        viewModelScope.launch {
            listarVehiculosUseCase(soloActivos = true).collect { lista ->
                _uiState.update { it.copy(vehiculos = lista, vehiculoId = it.vehiculoId ?: lista.firstOrNull()?.id) }
            }
        }
    }

    fun seleccionarZona(id: String) = _uiState.update { it.copy(zonaId = id) }

    fun seleccionarVehiculo(id: String) = _uiState.update { it.copy(vehiculoId = id) }

    fun abrirJornada() {
        val estado = _uiState.value
        val zonaId = estado.zonaId ?: return
        val vehiculoId = estado.vehiculoId ?: return

        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }
            val usuario = obtenerSesionUseCase().first()?.usuario
            if (usuario == null) {
                _uiState.update { it.copy(cargando = false, error = "No hay sesión activa.") }
                return@launch
            }
            abrirJornadaUseCase(usuario.id, zonaId, vehiculoId).fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, jornadaAbierta = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }
}
