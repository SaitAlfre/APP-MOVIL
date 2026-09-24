package pe.ecolecta.presentation.admin.traslados

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.traslado.AutorizarTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.CrearTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.ListarTrasladosUseCase
import pe.ecolecta.domain.usecase.traslado.RechazarTrasladoUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

class TrasladosViewModel(
    private val listarTrasladosUseCase: ListarTrasladosUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val crearTrasladoUseCase: CrearTrasladoUseCase,
    private val autorizarTrasladoUseCase: AutorizarTrasladoUseCase,
    private val rechazarTrasladoUseCase: RechazarTrasladoUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(TrasladosUiState())
    val uiState: StateFlow<TrasladosUiState> = _uiState.asStateFlow()

    private var usuarioActualId: String? = null

    init {
        viewModelScope.launch { obtenerSesionUseCase().collect { usuarioActualId = it?.usuario?.id } }
        viewModelScope.launch {
            cargaSegura { listarTrasladosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, traslados = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar los traslados.") } }
        }
        viewModelScope.launch {
            cargaSegura { listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
        viewModelScope.launch {
            // Todas las zonas, para nombrar también el origen de traslados antiguos; el diálogo ofrece solo las activas.
            cargaSegura { listarZonasUseCase(soloActivas = false).collect { lista -> _uiState.update { it.copy(zonas = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
    }

    fun abrir(dialogo: DialogoTraslado?) {
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(dialogo = dialogo, errorDialogo = null, mensaje = null) }
    }

    fun limpiarMensaje() = _uiState.update { it.copy(mensaje = null) }

    fun crear(proveedorId: String, zonaDestinoId: String, motivo: String) = ejecutar("El cambio de zona quedó por autorizar.") {
        crearTrasladoUseCase(proveedorId, zonaDestinoId, motivo).map { }
    }

    fun autorizar(id: String) = ejecutar("Traslado autorizado: el proveedor ya pertenece a la nueva zona. Quedó en Auditoría.") { adminId ->
        autorizarTrasladoUseCase(id, adminId)
    }

    fun rechazar(id: String, motivo: String) = ejecutar("Traslado rechazado. El proveedor sigue en su zona actual.") { adminId ->
        rechazarTrasladoUseCase(id, adminId, motivo)
    }

    private fun ejecutar(exito: String, accion: suspend (adminId: String) -> Result<Unit>) {
        val adminId = usuarioActualId ?: run {
            _uiState.update { it.copy(errorDialogo = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(procesando = true, errorDialogo = null) }
        viewModelScope.launch {
            cargaSegura { accion(adminId).getOrThrow() }.fold(
                onSuccess = { _uiState.update { it.copy(procesando = false, dialogo = null, mensaje = exito) } },
                onFailure = { e -> _uiState.update { it.copy(procesando = false, errorDialogo = e.message ?: "No se pudo completar la acción.") } },
            )
        }
    }
}
