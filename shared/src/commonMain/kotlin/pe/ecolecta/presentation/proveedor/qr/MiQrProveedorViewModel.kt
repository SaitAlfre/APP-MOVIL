package pe.ecolecta.presentation.proveedor.qr

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.generarQrProveedor
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

class MiQrProveedorViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerPerfilProveedorUseCase: ObtenerPerfilProveedorUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(MiQrProveedorUiState())
    val uiState: StateFlow<MiQrProveedorUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                val usuario = obtenerSesionUseCase().first()?.usuario ?: return@cargaSegura
                val proveedor = obtenerPerfilProveedorUseCase(usuario.id) ?: return@cargaSegura
                val zonas = listarZonasUseCase().first()

                _uiState.update {
                    it.copy(
                        cargando = false,
                        proveedor = proveedor,
                        nombreZona = zonas.firstOrNull { z -> z.id == proveedor.zonaId }?.nombre ?: proveedor.zonaId,
                        contenidoQr = generarQrProveedor(proveedor.id),
                    )
                }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo generar tu QR.") } }
        }
    }
}
