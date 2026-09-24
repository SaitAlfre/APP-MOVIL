package pe.ecolecta.presentation.admin.entregas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auditoria.ListarAuditoriaUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ObtenerEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.presentation.cargaSegura
import pe.ecolecta.presentation.design.formatearLitros

class EntregaDetalleViewModel(
    private val entregaId: String,
    private val obtenerEntregaUseCase: ObtenerEntregaUseCase,
    private val obtenerProveedorUseCase: ObtenerProveedorUseCase,
    private val corregirEntregaUseCase: CorregirEntregaUseCase,
    private val anularEntregaUseCase: AnularEntregaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val reglaEdicion: ReglaEdicionEntrega,
    private val listarAuditoria: ListarAuditoriaUseCase,
    private val listarUsuarios: ListarUsuariosUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(EntregaDetalleUiState())
    val uiState: StateFlow<EntregaDetalleUiState> = _uiState.asStateFlow()

    private var usuarioActualId: String? = null

    init {
        viewModelScope.launch { obtenerSesionUseCase().collect { usuarioActualId = it?.usuario?.id } }
        cargar()
    }

    private fun cargar() {
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = it.entrega == null, error = null) }
            cargaSegura {
                val entrega = obtenerEntregaUseCase(entregaId)
                val proveedor = entrega?.let { obtenerProveedorUseCase(it.proveedorId) }
                val bloqueo = entrega?.let { reglaEdicion.bloqueo(it)?.message }
                val historial = listarAuditoria.filtrar(entidad = "entrega").filter { it.entidadId == entregaId }
                    .sortedByDescending { it.ocurridoEn }
                val nombres = listarUsuarios().first().associate { it.id to it.nombres }
                Carga(entrega, proveedor?.nombres, bloqueo, historial, nombres)
            }.fold(
                onSuccess = { c ->
                    _uiState.update {
                        it.copy(
                            cargando = false,
                            entrega = c.entrega,
                            proveedorNombre = c.proveedor ?: "Proveedor no disponible",
                            bloqueo = c.bloqueo,
                            historial = c.historial,
                            nombresUsuarios = c.nombres,
                            error = if (c.entrega == null) "La entrega ya no existe en este dispositivo." else null,
                        )
                    }
                },
                onFailure = { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la entrega.") } },
            )
        }
    }

    fun abrirDialogo(dialogo: DialogoEntrega?) {
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(dialogo = dialogo, errorDialogo = null, mensaje = null) }
    }

    fun limpiarMensaje() = _uiState.update { it.copy(mensaje = null) }

    fun corregir(litros: Double, tachos: Int, motivo: String) {
        val estado = _uiState.value
        val entrega = estado.entrega ?: return
        val usuarioId = usuarioActualId ?: return errorDialogo("Todavía no se cargó tu sesión. Intenta de nuevo en un momento.")
        if (estado.procesando) return
        _uiState.update { it.copy(procesando = true, errorDialogo = null) }
        viewModelScope.launch {
            corregirEntregaUseCase(entregaId, litros, tachos, entrega.observaciones, motivo, usuarioId).fold(
                onSuccess = {
                    val cambio = "${formatearLitros(entrega.litros)} → ${formatearLitros(litros)}" +
                        if (tachos != entrega.tachos) ", ${entrega.tachos} → $tachos tachos" else ""
                    _uiState.update {
                        it.copy(
                            procesando = false, dialogo = null,
                            mensaje = "Corrección guardada ($cambio). Quedó en Auditoría y la entrega queda pendiente de envío.",
                        )
                    }
                    cargar()
                },
                onFailure = { e -> _uiState.update { it.copy(procesando = false, errorDialogo = e.message ?: "No se pudo corregir la entrega.") } },
            )
        }
    }

    fun anular(motivo: String) {
        val usuarioId = usuarioActualId ?: return errorDialogo("Todavía no se cargó tu sesión. Intenta de nuevo en un momento.")
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(procesando = true, errorDialogo = null) }
        viewModelScope.launch {
            anularEntregaUseCase(entregaId, motivo, usuarioId).fold(
                onSuccess = {
                    _uiState.update {
                        it.copy(procesando = false, dialogo = null, mensaje = "Entrega anulada. Ya no cuenta en litros ni liquidaciones.")
                    }
                    cargar()
                },
                onFailure = { e -> _uiState.update { it.copy(procesando = false, errorDialogo = e.message ?: "No se pudo anular la entrega.") } },
            )
        }
    }

    private fun errorDialogo(texto: String) = _uiState.update { it.copy(errorDialogo = texto) }

    private data class Carga(
        val entrega: pe.ecolecta.domain.model.Entrega?,
        val proveedor: String?,
        val bloqueo: String?,
        val historial: List<pe.ecolecta.domain.model.Auditoria>,
        val nombres: Map<String, String>,
    )
}
