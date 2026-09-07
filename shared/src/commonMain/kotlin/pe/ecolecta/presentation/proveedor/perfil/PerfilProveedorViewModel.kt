package pe.ecolecta.presentation.proveedor.perfil

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerEstadoSincronizacionUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.SincronizarDatosProveedorUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class PerfilProveedorViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerPerfilProveedorUseCase: ObtenerPerfilProveedorUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val obtenerEstadoSincronizacionUseCase: ObtenerEstadoSincronizacionUseCase,
    private val sincronizarDatosProveedorUseCase: SincronizarDatosProveedorUseCase,
    private val cerrarSesionUseCase: CerrarSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(PerfilProveedorUiState())
    val uiState: StateFlow<PerfilProveedorUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            val usuario = obtenerSesionUseCase().first()?.usuario ?: return@launch
            val proveedor = obtenerPerfilProveedorUseCase(usuario.id) ?: return@launch
            val zonas = listarZonasUseCase().first()
            val resumenSync = obtenerEstadoSincronizacionUseCase(proveedor.id)

            _uiState.update {
                it.copy(
                    cargando = false,
                    proveedor = proveedor,
                    nombreZona = zonas.firstOrNull { z -> z.id == proveedor.zonaId }?.nombre ?: proveedor.zonaId,
                    resumenSync = resumenSync,
                )
            }
        }
    }

    fun sincronizar() {
        _uiState.update { it.copy(mensajeSincronizar = sincronizarDatosProveedorUseCase()) }
    }

    fun cerrarSesion() {
        viewModelScope.launch { cerrarSesionUseCase() }
    }
}
