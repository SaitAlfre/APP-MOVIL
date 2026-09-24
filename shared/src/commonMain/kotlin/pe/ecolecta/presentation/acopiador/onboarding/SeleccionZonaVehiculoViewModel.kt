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
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.domain.acopio.cicloAcopioDe
import pe.ecolecta.domain.acopio.hoyAcopio

class SeleccionZonaVehiculoViewModel(
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val abrirJornadaUseCase: AbrirJornadaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val reloj: Reloj,
    private val cuentas: pe.ecolecta.domain.repository.CuentasRepository,
    private val cerrarSesionUseCase: CerrarSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(SeleccionZonaVehiculoUiState())
    val uiState: StateFlow<SeleccionZonaVehiculoUiState> = _uiState.asStateFlow()

    init {
        // El nombre de zona del ciclo se rellena al elegirla; aquí solo interesan las fechas.
        _uiState.update { it.copy(ciclo = cicloAcopioDe(hoyAcopio(reloj), zonaNombre = "")) }

        // Zonas, vehículos y la zona sugerida se cargan por separado. Antes las zonas esperaban a la
        // sesión y a `zonaAsignada` antes de empezar a cargarse: mientras tanto (o para siempre, si
        // esa consulta fallaba) la pantalla mostraba un formulario vacío con "Abrir jornada" apagado.
        viewModelScope.launch {
            listarZonasUseCase(soloActivas = true).collect { lista ->
                _uiState.update { s -> conZonaPreseleccionada(s.copy(zonas = lista, zonasCargadas = true)) }
            }
        }
        viewModelScope.launch {
            listarVehiculosUseCase(soloActivos = true).collect { lista ->
                _uiState.update { s ->
                    s.copy(
                        vehiculos = lista,
                        vehiculosCargados = true,
                        vehiculoId = s.vehiculoId?.takeIf { id -> lista.any { it.id == id } } ?: lista.firstOrNull()?.id,
                    )
                }
            }
        }
        viewModelScope.launch {
            // La zona que el administrador asignó a esta cuenta aparece preseleccionada si está activa.
            val asignada = runCatching {
                obtenerSesionUseCase().first()?.usuario?.id?.let { cuentas.zonaAsignada(it) }
            }.getOrNull() ?: return@launch
            _uiState.update { s -> conZonaPreseleccionada(s.copy(zonaAsignadaId = asignada)) }
        }
    }

    /** Asignada (si está activa) › la ya elegida (si sigue activa) › la primera. Nunca pisa una elección manual. */
    private fun conZonaPreseleccionada(s: SeleccionZonaVehiculoUiState): SeleccionZonaVehiculoUiState {
        val activas = s.zonas
        val actual = s.zonaId?.takeIf { id -> activas.any { it.id == id } }
        if (s.zonaElegidaManualmente && actual != null) return s
        val asignada = s.zonaAsignadaId?.takeIf { id -> activas.any { it.id == id } }
        return s.copy(zonaId = asignada ?: actual ?: activas.firstOrNull()?.id)
    }

    fun seleccionarZona(id: String) = _uiState.update { it.copy(zonaId = id, zonaElegidaManualmente = true) }

    fun seleccionarVehiculo(id: String) = _uiState.update { it.copy(vehiculoId = id) }

    /** "Cerrar sesión" debe existir también sin jornada abierta: si no, esta pantalla sería una trampa. */
    fun solicitarCierreSesion() = _uiState.update { it.copy(mostrarConfirmacionCierreSesion = true) }

    fun cancelarCierreSesion() = _uiState.update { it.copy(mostrarConfirmacionCierreSesion = false) }

    fun confirmarCierreSesion() {
        if (_uiState.value.cerrandoSesion) return
        _uiState.update { it.copy(mostrarConfirmacionCierreSesion = false, cerrandoSesion = true) }
        // No borra nada: solo quita la sesión guardada. App.kt vuelve al login al observar el cambio.
        viewModelScope.launch {
            runCatching { cerrarSesionUseCase() }
                .onFailure { _uiState.update { it.copy(cerrandoSesion = false, error = "No se pudo cerrar la sesión. Inténtalo de nuevo.") } }
        }
    }

    fun abrirJornada() {
        val estado = _uiState.value
        if (estado.cargando) return
        val zonaId = estado.zonaId ?: return
        val vehiculoId = estado.vehiculoId ?: return

        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null, jornadaAbierta = false) }
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
                        error.message ?: "No se pudo abrir la jornada."
                    }
                    _uiState.update { it.copy(cargando = false, error = mensaje) }
                },
            )
        }
    }

    /**
     * La pantalla la llama al navegar por [SeleccionZonaVehiculoUiState.jornadaAbierta]. Este ViewModel
     * vive toda la sesión (ver [pe.ecolecta.presentation.App]): si el éxito no se consume, tras cerrar
     * la jornada y volver aquí el `LaunchedEffect` lo vería aún en `true` y rebotaría al inicio sin abrir nada.
     */
    fun navegacionAJornadaAtendida() = _uiState.update { it.copy(jornadaAbierta = false) }
}
