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
    private var resolviendo = false

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

    fun resolver(entregaId: String, origen: OrigenValorConflicto, motivo: String) {
        val usuarioId = usuarioActualId ?: run {
            _uiState.update { it.copy(error = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        if (resolviendo) return
        resolviendo = true
        viewModelScope.launch {
            cargaSegura { resolverConflictoUseCase(entregaId, origen, motivo, usuarioId) }.fold(
                onSuccess = { resultado ->
                    resultado.onFailure { error -> _uiState.update { it.copy(error = error.message ?: "No se pudo resolver el conflicto.") } }
                },
                onFailure = { e -> _uiState.update { it.copy(error = e.message ?: "No se pudo resolver el conflicto.") } },
            )
            resolviendo = false
        }
    }
}
