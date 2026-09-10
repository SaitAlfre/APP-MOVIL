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
    private var accionEnCurso = false

    init {
        viewModelScope.launch { obtenerSesionUseCase().collect { usuarioActualId = it?.usuario?.id } }
        viewModelScope.launch {
            listarTrasladosUseCase().collect { lista -> _uiState.update { it.copy(cargando = false, traslados = lista) } }
        }
        viewModelScope.launch {
            listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } }
        }
        viewModelScope.launch {
            listarZonasUseCase(soloActivas = true).collect { lista -> _uiState.update { it.copy(zonas = lista) } }
        }
    }

    fun mostrarDialogoCrear(mostrar: Boolean) {
        _uiState.update { it.copy(mostrarDialogoCrear = mostrar, error = null) }
    }

    fun crear(proveedorId: String, zonaDestinoId: String, motivo: String) {
        if (accionEnCurso) return
        accionEnCurso = true
        viewModelScope.launch {
            crearTrasladoUseCase(proveedorId, zonaDestinoId, motivo)
                .onSuccess { _uiState.update { it.copy(mostrarDialogoCrear = false) } }
                .onFailure { error -> _uiState.update { it.copy(error = error.message) } }
            accionEnCurso = false
        }
    }

    fun autorizar(id: String) {
        val adminId = usuarioActualId ?: return
        if (accionEnCurso) return
        accionEnCurso = true
        viewModelScope.launch {
            autorizarTrasladoUseCase(id, adminId).onFailure { error -> _uiState.update { it.copy(error = error.message) } }
            accionEnCurso = false
        }
    }

    fun rechazar(id: String, motivo: String) {
        val adminId = usuarioActualId ?: return
        if (accionEnCurso) return
        accionEnCurso = true
        viewModelScope.launch {
            rechazarTrasladoUseCase(id, adminId, motivo).onFailure { error -> _uiState.update { it.copy(error = error.message) } }
            accionEnCurso = false
        }
    }
}
