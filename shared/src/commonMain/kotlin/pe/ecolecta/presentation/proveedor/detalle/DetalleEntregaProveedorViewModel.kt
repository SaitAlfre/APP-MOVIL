package pe.ecolecta.presentation.proveedor.detalle

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerDetalleEntregaUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

class DetalleEntregaProveedorViewModel(
    private val entregaId: String,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerPerfilProveedorUseCase: ObtenerPerfilProveedorUseCase,
    private val obtenerDetalleEntregaUseCase: ObtenerDetalleEntregaUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val listarVehiculosUseCase: ListarVehiculosUseCase,
    private val listarUsuariosUseCase: ListarUsuariosUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(DetalleEntregaProveedorUiState())
    val uiState: StateFlow<DetalleEntregaProveedorUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                val usuario = obtenerSesionUseCase().first()?.usuario ?: return@cargaSegura
                val proveedor = obtenerPerfilProveedorUseCase(usuario.id) ?: return@cargaSegura

                // ObtenerDetalleEntregaUseCase solo devuelve la entrega si en verdad pertenece a este proveedor (§11, §39).
                val entrega = obtenerDetalleEntregaUseCase(entregaId, proveedor.id)
                if (entrega == null) {
                    _uiState.update { it.copy(cargando = false, noEncontrada = true) }
                    return@cargaSegura
                }

                val zonas = listarZonasUseCase().first()
                val vehiculos = listarVehiculosUseCase().first()
                val usuarios = listarUsuariosUseCase().first()

                _uiState.update {
                    it.copy(
                        cargando = false,
                        entrega = entrega,
                        nombreZona = zonas.firstOrNull { z -> z.id == entrega.zonaId }?.nombre ?: entrega.zonaId,
                        nombreVehiculo = vehiculos.firstOrNull { v -> v.id == entrega.vehiculoId }?.nombre ?: entrega.vehiculoId,
                        nombreAcopiador = usuarios.firstOrNull { u -> u.id == entrega.usuarioId }?.nombres ?: entrega.usuarioId,
                    )
                }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la entrega.") } }
        }
    }
}
