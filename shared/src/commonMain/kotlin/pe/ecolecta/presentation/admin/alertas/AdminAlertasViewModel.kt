package pe.ecolecta.presentation.admin.alertas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.AlertaAdmin
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.domain.model.TipoAlertaAdmin
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.GestionPortalRepository
import pe.ecolecta.domain.usecase.admin.ObservarAlertasAdminUseCase
import pe.ecolecta.domain.usecase.admin.OcultarAlertaUseCase
import pe.ecolecta.domain.usecase.admin.ResolverSolicitudUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.traslado.AutorizarTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.RechazarTrasladoUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

data class AdminAlertasUiState(
    val cargando: Boolean = true,
    val alertas: List<AlertaAdmin> = emptyList(),
    val solicitudes: List<SolicitudProveedor> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val procesando: Set<String> = emptySet(),
    val mensaje: String? = null,
    val error: String? = null,
) {
    fun solicitudDe(alerta: AlertaAdmin): SolicitudProveedor? =
        if (alerta.id.startsWith("solicitud:")) solicitudes.firstOrNull { it.id == alerta.referenciaId } else null

    fun cuenta(tipo: TipoAlertaAdmin) = alertas.count { it.tipo == tipo }
}

/** Decisiones sobre la bandeja de alertas; lo usan Inicio (acciones pendientes) y Alertas. */
class AdminAlertasViewModel(
    private val observarAlertas: ObservarAlertasAdminUseCase,
    private val ocultarAlerta: OcultarAlertaUseCase,
    private val resolverSolicitud: ResolverSolicitudUseCase,
    private val autorizarTraslado: AutorizarTrasladoUseCase,
    private val rechazarTraslado: RechazarTrasladoUseCase,
    private val portal: GestionPortalRepository,
    private val listarZonas: ListarZonasUseCase,
    private val obtenerSesion: ObtenerSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminAlertasUiState())
    val uiState: StateFlow<AdminAlertasUiState> = _uiState.asStateFlow()

    private var adminId: String? = null

    init {
        viewModelScope.launch { obtenerSesion().collect { adminId = it?.usuario?.id } }
        viewModelScope.launch {
            cargaSegura { observarAlertas().collect { lista -> _uiState.update { it.copy(cargando = false, alertas = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las alertas.") } }
        }
        viewModelScope.launch {
            cargaSegura { portal.todasSolicitudes().collect { lista -> _uiState.update { it.copy(solicitudes = lista) } } }
        }
        viewModelScope.launch {
            cargaSegura { listarZonas().collect { lista -> _uiState.update { it.copy(zonas = lista) } } }
        }
    }

    /** Aprobar traslado o marcar reclamo como atendido. */
    fun aprobar(alerta: AlertaAdmin) = ejecutar(alerta, exito = if (alerta.tipo == TipoAlertaAdmin.TRASLADO) "Traslado autorizado. El proveedor ya pertenece a su nueva zona." else "Reclamo marcado como atendido.") { admin ->
        when {
            alerta.id.startsWith("solicitud:") -> resolverSolicitud(alerta.referenciaId, aprobar = true, adminId = admin)
            alerta.id.startsWith("traslado:") -> autorizarTraslado(alerta.referenciaId, admin)
            else -> Result.failure(IllegalStateException("Esta alerta se revisa desde su módulo."))
        }
    }

    /** Rechaza la solicitud o traslado; conflictos y calidad solo se ocultan de la bandeja. */
    fun rechazar(alerta: AlertaAdmin, motivo: String) = ejecutar(alerta, exito = "Solicitud rechazada.") { admin ->
        when {
            alerta.id.startsWith("solicitud:") -> resolverSolicitud(alerta.referenciaId, aprobar = false, adminId = admin)
            alerta.id.startsWith("traslado:") -> rechazarTraslado(alerta.referenciaId, admin, motivo.ifBlank { "Rechazado desde alertas" })
            else -> ocultarAlerta(alerta.id, admin)
        }
    }

    fun ocultar(alerta: AlertaAdmin) = ejecutar(alerta, exito = "Alerta ocultada. El registro original no cambia.") { admin ->
        ocultarAlerta(alerta.id, admin)
    }

    fun limpiarMensaje() = _uiState.update { it.copy(mensaje = null, error = null) }

    private fun ejecutar(alerta: AlertaAdmin, exito: String, accion: suspend (String) -> Result<Unit>) {
        val admin = adminId ?: run {
            _uiState.update { it.copy(error = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        if (alerta.id in _uiState.value.procesando) return
        _uiState.update { it.copy(procesando = it.procesando + alerta.id, mensaje = null, error = null) }
        viewModelScope.launch {
            val resultado = cargaSegura { accion(admin).getOrThrow() }
            _uiState.update {
                it.copy(
                    procesando = it.procesando - alerta.id,
                    mensaje = if (resultado.isSuccess) exito else null,
                    error = resultado.exceptionOrNull()?.let { e -> e.message ?: "No se pudo completar la acción." },
                )
            }
        }
    }
}
