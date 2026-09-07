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
            listarConflictosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, conflictos = lista) } }
        }
        viewModelScope.launch {
            listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } }
        }
    }

    fun resolver(entregaId: String, origen: OrigenValorConflicto, motivo: String) {
        val usuarioId = usuarioActualId ?: return
        viewModelScope.launch { resolverConflictoUseCase(entregaId, origen, motivo, usuarioId) }
    }
}
