package pe.ecolecta.presentation.acopiador.onboarding

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.ZonaOcupadaException
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.acopiador.ciclo.cicloSimuladoDe

class SeleccionZonaVehiculoViewModel(
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val abrirJornadaUseCase: AbrirJornadaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val reloj: Reloj,
    private val cuentas: pe.ecolecta.domain.repository.CuentasRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(SeleccionZonaVehiculoUiState())
    val uiState: StateFlow<SeleccionZonaVehiculoUiState> = _uiState.asStateFlow()

    init {
        // El nombre de zona del ciclo se rellena al elegirla; aquí solo interesan las fechas.
        _uiState.update { it.copy(ciclo = cicloSimuladoDe(reloj.hoy(), zonaNombre = "")) }

        viewModelScope.launch {
            // La zona que el administrador asignó a esta cuenta aparece preseleccionada.
            val sugerida = obtenerSesionUseCase().first()?.usuario?.id?.let { cuentas.zonaAsignada(it) }
            listarZonasUseCase(soloActivas = true).collect { lista ->
                _uiState.update { s ->
                    s.copy(zonas = lista, zonaId = s.zonaId ?: sugerida?.takeIf { id -> lista.any { it.id == id } } ?: lista.firstOrNull()?.id)
                }
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
        if (estado.cargando) return
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
                onFailure = { error ->
                    val mensaje = if (error is ZonaOcupadaException) {
                        val nombreZona = estado.zonas.firstOrNull { it.id == zonaId }?.nombre ?: "seleccionada"
                        "La zona $nombreZona ya tiene un acopiador con una jornada abierta. Elige otra zona o coordina con él."
                    } else {
                        error.message
                    }
                    _uiState.update { it.copy(cargando = false, error = mensaje) }
                },
            )
        }
    }
}
