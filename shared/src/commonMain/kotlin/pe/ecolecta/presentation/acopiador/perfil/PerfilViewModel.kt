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
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.seguimiento.ObtenerIdentidadRemotaUseCase
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
    private val cerrarJornadaUseCase: CerrarJornadaUseCase,
    private val obtenerIdentidadRemotaUseCase: ObtenerIdentidadRemotaUseCase,
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

            val uid = obtenerIdentidadRemotaUseCase()
            _uiState.update { it.copy(uidFirebase = uid) }
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

    /** A diferencia del cierre de sesión, siempre pide confirmación (haya o no pendientes por sincronizar). */
    fun solicitarCierreJornada() {
        if (_uiState.value.jornadaId == null) return
        _uiState.update { it.copy(mostrarConfirmacionCierreJornada = true) }
    }

    fun confirmarCierreJornada() {
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
}
