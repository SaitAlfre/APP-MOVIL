package pe.ecolecta.presentation.admin.entregas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ObtenerEntregaUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorUseCase
import pe.ecolecta.presentation.cargaSegura

class EntregaDetalleViewModel(
    private val entregaId: String,
    private val obtenerEntregaUseCase: ObtenerEntregaUseCase,
    private val obtenerProveedorUseCase: ObtenerProveedorUseCase,
    private val corregirEntregaUseCase: CorregirEntregaUseCase,
    private val anularEntregaUseCase: AnularEntregaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
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
            _uiState.update { it.copy(cargando = true, error = null) }
            cargaSegura {
                val entrega = obtenerEntregaUseCase(entregaId)
                val proveedor = entrega?.let { obtenerProveedorUseCase(it.proveedorId) }
                entrega to proveedor
            }.fold(
                onSuccess = { (entrega, proveedor) ->
                    _uiState.update { it.copy(cargando = false, entrega = entrega, proveedorNombre = proveedor?.nombres.orEmpty()) }
                },
                onFailure = { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la entrega.") } },
            )
        }
    }

    fun corregir(litros: Double, tachos: Int, motivo: String) {
        val usuarioId = usuarioActualId ?: run {
            _uiState.update { it.copy(error = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        val observaciones = _uiState.value.entrega?.observaciones
        viewModelScope.launch {
            corregirEntregaUseCase(entregaId, litros, tachos, observaciones, motivo, usuarioId).fold(
                onSuccess = { cargar() },
                onFailure = { error -> _uiState.update { it.copy(error = error.message) } },
            )
        }
    }

    fun anular(motivo: String) {
        val usuarioId = usuarioActualId ?: run {
            _uiState.update { it.copy(error = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        viewModelScope.launch {
            anularEntregaUseCase(entregaId, motivo, usuarioId).fold(
                onSuccess = { cargar() },
                onFailure = { error -> _uiState.update { it.copy(error = error.message) } },
            )
        }
    }
}
