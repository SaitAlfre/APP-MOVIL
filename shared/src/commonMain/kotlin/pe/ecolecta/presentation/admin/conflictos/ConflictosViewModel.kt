package pe.ecolecta.presentation.admin.conflictos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.OrigenValorConflicto
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.conflicto.ListarConflictosUseCase
import pe.ecolecta.domain.usecase.conflicto.ResolverConflictoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.presentation.cargaSegura

class ConflictosViewModel(
    private val listarConflictosUseCase: ListarConflictosUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
    private val resolverConflictoUseCase: ResolverConflictoUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ConflictosUiState())
    val uiState: StateFlow<ConflictosUiState> = _uiState.asStateFlow()

    private var usuarioActualId: String? = null

    init {
        viewModelScope.launch { obtenerSesionUseCase().collect { usuarioActualId = it?.usuario?.id } }
        viewModelScope.launch {
            cargaSegura {
                listarConflictosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, conflictos = lista) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar los conflictos.") } }
        }
        viewModelScope.launch {
            cargaSegura { listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
    }

    fun pedirMotivo(entregaId: String, origen: OrigenValorConflicto) {
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(resolucion = entregaId to origen, errorDialogo = null, mensaje = null) }
    }

    fun cancelar() {
        if (!_uiState.value.procesando) _uiState.update { it.copy(resolucion = null, errorDialogo = null) }
    }

    fun limpiarMensaje() = _uiState.update { it.copy(mensaje = null) }

    fun resolver(motivo: String) {
        val (entregaId, origen) = _uiState.value.resolucion ?: return
        val usuarioId = usuarioActualId ?: run {
            _uiState.update { it.copy(errorDialogo = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(procesando = true, errorDialogo = null) }
        viewModelScope.launch {
            val resultado = cargaSegura { resolverConflictoUseCase(entregaId, origen, motivo, usuarioId).getOrThrow() }
            resultado.fold(
                onSuccess = {
                    val texto = when (origen) {
                        OrigenValorConflicto.LOCAL -> "Se conservó el valor del teléfono. La entrega quedó pendiente de envío para reemplazar el del servidor."
                        OrigenValorConflicto.SERVIDOR -> "Se aplicó el valor del servidor. La entrega quedó sincronizada."
                    }
                    _uiState.update { it.copy(procesando = false, resolucion = null, mensaje = "$texto Quedó registrado en Auditoría.") }
                },
                onFailure = { e -> _uiState.update { it.copy(procesando = false, errorDialogo = e.message ?: "No se pudo resolver el conflicto.") } },
            )
        }
    }
}
