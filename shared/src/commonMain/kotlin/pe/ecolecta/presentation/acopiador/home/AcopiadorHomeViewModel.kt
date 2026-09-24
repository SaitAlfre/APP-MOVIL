@file:OptIn(ExperimentalCoroutinesApi::class)

package pe.ecolecta.presentation.acopiador.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.ReaperturaJornadaException
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CredencialAdmin
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaTerminadaHoyUseCase
import pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.design.formatearHoraAcopio

class AcopiadorHomeViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val observarEntregasDeJornadaUseCase: ObservarEntregasDeJornadaUseCase,
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val corregirEntregaUseCase: CorregirEntregaUseCase,
    private val anularEntregaUseCase: AnularEntregaUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val cerrarJornadaUseCase: CerrarJornadaUseCase,
    private val obtenerJornadaTerminadaHoyUseCase: ObtenerJornadaTerminadaHoyUseCase,
    private val reabrirJornadaUseCase: ReabrirJornadaUseCase,
    private val cerrarSesionUseCase: CerrarSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AcopiadorHomeUiState())
    val uiState: StateFlow<AcopiadorHomeUiState> = _uiState.asStateFlow()

    private var usuarioIdActual: String? = null
    private var jornadaActual: Jornada? = null

    init {
        viewModelScope.launch {
            val sesion = obtenerSesionUseCase().first()
            val usuarioId = sesion?.usuario?.id
            usuarioIdActual = usuarioId

            obtenerJornadaEnCursoUseCase()
                .flatMapLatest { enCurso ->
                    // Sin jornada en curso se busca la de hoy ya cerrada: el inicio muestra su resumen
                    // (entregas, pendientes de sync) en vez de ofrecer abrir otra, que no está permitido.
                    val jornada = enCurso ?: usuarioId?.let { runCatching { obtenerJornadaTerminadaHoyUseCase(it) }.getOrNull() }
                    jornadaActual = jornada
                    if (jornada == null) {
                        flowOf(Triple(emptyList<Entrega>(), emptyList<Proveedor>(), false))
                    } else {
                        combine(
                            observarEntregasDeJornadaUseCase(jornada.id),
                            listarProveedoresPorZonaUseCase(jornada.zonaId),
                        ) { entregas, proveedores -> Triple(entregas, proveedores, jornada.estaAbierta) }
                    }
                }
                .collect { (entregas, proveedores, jornadaAbierta) ->
                    val pendientes = usuarioId?.let { obtenerColaSyncUseCase(it).pendientes } ?: 0
                    val jornada = jornadaActual
                    val zonaNombre = jornada?.let { j -> listarZonasUseCase().first().firstOrNull { it.id == j.zonaId }?.nombre }.orEmpty()
                    val vehiculo = jornada?.let { j -> listarVehiculosUseCase().first().firstOrNull { it.id == j.vehiculoId } }
                    _uiState.update {
                        it.copy(
                            cargando = false,
                            nombreUsuario = sesion?.usuario?.nombres.orEmpty(),
                            zonaNombre = zonaNombre,
                            vehiculoInfo = vehiculo?.let { v -> "${v.nombre} · ${v.placa}" }.orEmpty(),
                            horaInicio = jornada?.let { j -> formatearHoraAcopio(j.abiertaEn) }.orEmpty(),
                            litrosHoy = entregas.filterNot(Entrega::anulada).sumOf(Entrega::litros),
                            entregasHoy = entregas.count { e -> !e.anulada },
                            pendientesSync = pendientes,
                            entregas = entregas,
                            proveedores = proveedores,
                            jornadaId = jornada?.id,
                            jornadaAbierta = jornadaAbierta,
                            horaCierre = jornada?.cerradaEn?.let(::formatearHoraAcopio),
                        )
                    }
                }
        }
    }

    fun corregir(entregaId: String, litros: Double, tachos: Int, motivo: String) {
        val usuarioId = usuarioIdActual ?: return
        viewModelScope.launch { corregirEntregaUseCase(entregaId, litros, tachos, observaciones = null, motivo = motivo, usuarioId = usuarioId) }
    }

    fun anular(entregaId: String, motivo: String) {
        val usuarioId = usuarioIdActual ?: return
        viewModelScope.launch { anularEntregaUseCase(entregaId, motivo, usuarioId) }
    }

    /** Siempre pide confirmación, haya o no pendientes por sincronizar: es una acción que cierra el día. */
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
                onSuccess = { _uiState.update { it.copy(cerrandoJornada = false) } },
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

    fun solicitarReapertura() {
        val jornada = jornadaActual?.takeIf { !it.estaAbierta } ?: return
        _uiState.update {
            it.copy(
                mostrarDialogoReapertura = true,
                reaperturaRequiereAdmin = reabrirJornadaUseCase.requiereAutorizacion(jornada),
                plazoReaperturaMinutos = reabrirJornadaUseCase.plazoMinutos,
                errorReapertura = null,
            )
        }
    }

    fun confirmarReapertura(motivo: String, usuarioAdmin: String, pinAdmin: String) {
        if (_uiState.value.reabriendo) return
        val jornada = jornadaActual?.takeIf { !it.estaAbierta } ?: return
        val credencial = if (usuarioAdmin.isNotBlank() && pinAdmin.isNotBlank()) CredencialAdmin(usuarioAdmin.trim(), pinAdmin) else null
        _uiState.update { it.copy(reabriendo = true, errorReapertura = null) }
        viewModelScope.launch {
            reabrirJornadaUseCase(jornada.id, jornada.usuarioId, motivo, credencial).fold(
                // La jornada en curso cambia a la reabierta y el flujo de arriba repinta el inicio.
                onSuccess = { _uiState.update { it.copy(reabriendo = false, mostrarDialogoReapertura = false) } },
                onFailure = { error ->
                    _uiState.update {
                        it.copy(
                            reabriendo = false,
                            reaperturaRequiereAdmin = it.reaperturaRequiereAdmin || error is ReaperturaJornadaException.FueraDePlazo,
                            errorReapertura = error.message ?: "No se pudo reabrir la jornada.",
                        )
                    }
                },
            )
        }
    }

    fun cancelarReapertura() = _uiState.update { it.copy(mostrarDialogoReapertura = false, errorReapertura = null) }

    /** Disponible con la jornada terminada: salir no debe depender de tener una jornada abierta. */
    fun solicitarCierreSesion() = _uiState.update { it.copy(mostrarConfirmacionCierreSesion = true) }

    fun cancelarCierreSesion() = _uiState.update { it.copy(mostrarConfirmacionCierreSesion = false) }

    fun confirmarCierreSesion() {
        if (_uiState.value.cerrandoSesion) return
        _uiState.update { it.copy(mostrarConfirmacionCierreSesion = false, cerrandoSesion = true) }
        // No borra entregas ni pendientes: solo quita la sesión guardada; App.kt vuelve al login.
        viewModelScope.launch {
            runCatching { cerrarSesionUseCase() }.onFailure { _uiState.update { it.copy(cerrandoSesion = false) } }
        }
    }
}
