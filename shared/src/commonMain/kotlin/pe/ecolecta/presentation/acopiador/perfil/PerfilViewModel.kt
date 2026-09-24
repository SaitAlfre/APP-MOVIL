package pe.ecolecta.presentation.acopiador.perfil

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerIdentidadRemotaUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.usuario.CambiarPinUsuarioUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class PerfilViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val cerrarSesionUseCase: CerrarSesionUseCase,
    private val cerrarJornadaUseCase: CerrarJornadaUseCase,
    private val obtenerIdentidadRemotaUseCase: ObtenerIdentidadRemotaUseCase,
    private val cambiarPinUsuarioUseCase: CambiarPinUsuarioUseCase,
    private val servidorWeb: ServidorWebRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(PerfilUiState())
    val uiState: StateFlow<PerfilUiState> = _uiState.asStateFlow()

    init {
        cargar()
    }

    /**
     * Vuelve a consultar sesión/jornada/cola de sync. [PerfilViewModel] vive toda la sesión (ver
     * [pe.ecolecta.presentation.App]), así que sin esto los "pendientes por sincronizar" se congelan
     * en el valor del primer ingreso a esta pestaña y nunca reflejan entregas registradas después.
     */
    fun cargar() {
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
                    jornadaId = jornada?.id,
                    jornadaAbierta = jornada?.estaAbierta == true,
                    usuarioIdLocal = sesion.usuario.id,
                )
            }

            val sesionPanel = if (servidorWeb.configurado) servidorWeb.observarSesion(sesion.usuario.id).first() else null
            _uiState.update { it.copy(sesionPanelWeb = sesionPanel) }

            val uid = obtenerIdentidadRemotaUseCase()
            _uiState.update { it.copy(uidFirebase = uid) }
        }
    }

    fun solicitarCierreSesion() {
        // Con jornada abierta también se confirma: hay que explicar que la jornada se conserva.
        if (_uiState.value.pendientesSync > 0 || _uiState.value.jornadaAbierta) {
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

    /** A diferencia del cierre de sesión, siempre pide confirmación (haya o no pendientes por sincronizar). */
    fun solicitarCierreJornada() {
        if (_uiState.value.jornadaId == null) return
        _uiState.update { it.copy(mostrarConfirmacionCierreJornada = true) }
    }

    fun confirmarCierreJornada() {
        if (_uiState.value.cerrandoJornada) return
        val jornadaId = _uiState.value.jornadaId ?: return
        _uiState.update { it.copy(mostrarConfirmacionCierreJornada = false, cerrandoJornada = true, errorCierreJornada = null) }
        viewModelScope.launch {
            cerrarJornadaUseCase(jornadaId).fold(
                onSuccess = {
                    _uiState.update {
                        it.copy(
                            cerrandoJornada = false,
                            jornadaAbierta = false,
                            jornadaId = null,
                            zonaActual = "—",
                            vehiculoActual = "—",
                        )
                    }
                },
                onFailure = { error ->
                    // La jornada sigue abierta: el use case no la marca cerrada si esto falla.
                    _uiState.update {
                        it.copy(cerrandoJornada = false, errorCierreJornada = error.message ?: "No se pudo cerrar la jornada.")
                    }
                },
            )
        }
    }

    fun cancelarCierreJornada() = _uiState.update { it.copy(mostrarConfirmacionCierreJornada = false) }

    fun descartarErrorCierreJornada() = _uiState.update { it.copy(errorCierreJornada = null) }

    fun solicitarCambiarPin() = _uiState.update { it.copy(mostrarCambiarPin = true, errorCambiarPin = null) }

    fun cancelarCambiarPin() = _uiState.update { it.copy(mostrarCambiarPin = false, errorCambiarPin = null) }

    fun cambiarPin(nuevoPin: String, confirmacionPin: String) {
        val id = _uiState.value.usuarioIdLocal
        if (id.isBlank()) return
        _uiState.update { it.copy(cambiandoPin = true, errorCambiarPin = null) }
        viewModelScope.launch {
            cambiarPinUsuarioUseCase(id, nuevoPin, confirmacionPin).fold(
                onSuccess = {
                    _uiState.update { it.copy(cambiandoPin = false, mostrarCambiarPin = false, pinCambiadoExitosamente = true) }
                },
                onFailure = { error ->
                    _uiState.update { it.copy(cambiandoPin = false, errorCambiarPin = error.message ?: "No se pudo cambiar el PIN.") }
                },
            )
        }
    }

    fun descartarPinCambiado() = _uiState.update { it.copy(pinCambiadoExitosamente = false) }

    /** "Actualizar mis datos": vuelve a leer zona, vehículo y cola de sincronización de este celular. */
    fun actualizarDatos() {
        cargar()
        _uiState.update { it.copy(mensajeDescarga = "Datos actualizados.") }
    }
}
