package pe.ecolecta.presentation.acopiador.perfil

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class PerfilViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val cerrarSesionUseCase: CerrarSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(PerfilUiState())
    val uiState: StateFlow<PerfilUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            val sesion = obtenerSesionUseCase().first() ?: return@launch
            val jornada = obtenerJornadaEnCursoUseCase().first()
            val zonas = listarZonasUseCase().first()
            val vehiculos = listarVehiculosUseCase().first()
            val resumen = obtenerColaSyncUseCase(sesion.usuario.id)

            _uiState.update {
                it.copy(
                    nombres = sesion.usuario.nombres,
                    username = sesion.usuario.username,
                    rol = sesion.rolActivo.name,
                    zonaActual = jornada?.let { j -> zonas.firstOrNull { z -> z.id == j.zonaId }?.nombre } ?: "—",
                    vehiculoActual = jornada?.let { j -> vehiculos.firstOrNull { v -> v.id == j.vehiculoId }?.nombre } ?: "—",
                    pendientesSync = resumen.pendientes,
                )
            }
        }
    }

    fun solicitarCierreSesion() {
        if (_uiState.value.pendientesSync > 0) {
            _uiState.update { it.copy(mostrarConfirmacionCierre = true) }
        } else {
            cerrarSesion()
        }
    }

    fun confirmarCierreSesion() {
        _uiState.update { it.copy(mostrarConfirmacionCierre = false) }
        cerrarSesion()
    }

    fun cancelarCierreSesion() = _uiState.update { it.copy(mostrarConfirmacionCierre = false) }

    private fun cerrarSesion() {
        viewModelScope.launch {
            cerrarSesionUseCase()
            _uiState.update { it.copy(sesionCerrada = true) }
        }
    }
}
