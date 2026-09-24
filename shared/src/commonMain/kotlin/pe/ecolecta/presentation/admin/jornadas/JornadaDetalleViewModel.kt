package pe.ecolecta.presentation.admin.jornadas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.usuario.ObtenerUsuarioUseCase
import pe.ecolecta.domain.usecase.vehiculo.ObtenerVehiculoUseCase
import pe.ecolecta.domain.usecase.zona.ObtenerZonaUseCase
import pe.ecolecta.presentation.cargaSegura

class JornadaDetalleViewModel(
    private val jornadaId: String,
    private val obtenerJornadaUseCase: ObtenerJornadaUseCase,
    private val observarEntregasDeJornadaUseCase: ObservarEntregasDeJornadaUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
    private val obtenerUsuario: ObtenerUsuarioUseCase,
    private val obtenerZona: ObtenerZonaUseCase,
    private val obtenerVehiculo: ObtenerVehiculoUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(JornadaDetalleUiState())
    val uiState: StateFlow<JornadaDetalleUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                val jornada = obtenerJornadaUseCase(jornadaId) ?: error("La jornada ya no existe en este dispositivo.")
                val acopiador = obtenerUsuario(jornada.usuarioId)?.let { "${it.nombres} · @${it.username}" } ?: "Acopiador no disponible"
                val zona = obtenerZona(jornada.zonaId)?.nombre ?: "Zona no disponible"
                val vehiculo = obtenerVehiculo(jornada.vehiculoId)?.let { "${it.nombre} · ${it.placa}" } ?: "Vehículo no disponible"
                _uiState.update { it.copy(jornada = jornada, acopiador = acopiador, zona = zona, vehiculo = vehiculo) }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la jornada.") } }
        }
        viewModelScope.launch {
            cargaSegura {
                observarEntregasDeJornadaUseCase(jornadaId).collect { entregas ->
                    _uiState.update { it.copy(cargando = false, entregas = entregas) }
                }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las entregas.") } }
        }
        viewModelScope.launch {
            cargaSegura {
                listarProveedoresUseCase().collect { lista -> _uiState.update { it.copy(proveedores = lista) } }
            }.onFailure { e -> _uiState.update { it.copy(error = e.message ?: "No se pudieron cargar los proveedores.") } }
        }
    }
}
