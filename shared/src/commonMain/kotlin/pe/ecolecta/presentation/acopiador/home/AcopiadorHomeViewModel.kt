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
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.seguimiento.DetenerSeguimientoUseCase
import pe.ecolecta.domain.usecase.seguimiento.IniciarSeguimientoUseCase
import pe.ecolecta.domain.usecase.seguimiento.ObtenerEstadoSeguimientoUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.design.formatearHora

class AcopiadorHomeViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val observarEntregasDeJornadaUseCase: ObservarEntregasDeJornadaUseCase,
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val corregirEntregaUseCase: CorregirEntregaUseCase,
    private val anularEntregaUseCase: AnularEntregaUseCase,
    private val iniciarSeguimientoUseCase: IniciarSeguimientoUseCase,
    private val detenerSeguimientoUseCase: DetenerSeguimientoUseCase,
    private val obtenerEstadoSeguimientoUseCase: ObtenerEstadoSeguimientoUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val cerrarJornadaUseCase: CerrarJornadaUseCase,
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
                .flatMapLatest { jornada ->
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
                            horaInicio = jornada?.let { j -> formatearHora(j.abiertaEn) }.orEmpty(),
                            litrosHoy = entregas.filterNot(Entrega::anulada).sumOf(Entrega::litros),
                            entregasHoy = entregas.count { e -> !e.anulada },
                            pendientesSync = pendientes,
                            entregas = entregas,
                            proveedores = proveedores,
                            jornadaId = jornada?.id,
                            jornadaAbierta = jornadaAbierta,
                        )
                    }
                }
        }

        viewModelScope.launch {
            obtenerEstadoSeguimientoUseCase().collect { estado ->
                _uiState.update { it.copy(estadoSeguimiento = estado) }
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

    /** Llamado desde la UI con el resultado del diálogo nativo de permiso de ubicación. */
    fun onPermisoUbicacionResultado(concedido: Boolean) {
        if (concedido) {
            _uiState.update { it.copy(mostrarAvisoPermisoDenegado = false) }
            viewModelScope.launch { iniciarSeguimientoUseCase() }
        } else {
            _uiState.update { it.copy(mostrarAvisoPermisoDenegado = true) }
        }
    }

    fun detenerSeguimiento() {
        val jornada = jornadaActual ?: return
        viewModelScope.launch {
            detenerSeguimientoUseCase(
                usuarioId = jornada.usuarioId,
                zonaId = jornada.zonaId,
                jornadaId = jornada.id,
                jornadaAbiertaEn = jornada.abiertaEn,
            )
        }
    }

    fun descartarAvisoPermiso() {
        _uiState.update { it.copy(mostrarAvisoPermisoDenegado = false) }
    }

    /** Siempre pide confirmación, haya o no pendientes por sincronizar: es una acción que cierra el día. */
    fun solicitarCierreJornada() {
        if (_uiState.value.jornadaId == null) return
        _uiState.update { it.copy(mostrarConfirmacionCierreJornada = true) }
    }

    fun confirmarCierreJornada() {
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
}
