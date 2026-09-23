package pe.ecolecta.presentation.admin.vehiculos

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.flowOn
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.usecase.jornada.ObservarJornadasUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.vehiculo.ActualizarVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.CrearVehiculoUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.vehiculo.ObtenerVehiculoUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

/** Un vehículo con su uso: si está en ruta ahora y cuántas jornadas ha hecho. */
data class VehiculoFila(val vehiculo: Vehiculo, val enRuta: String?, val jornadas: Int)

data class VehiculosUiState(val cargando: Boolean = true, val vehiculos: List<VehiculoFila> = emptyList(), val error: String? = null)

class VehiculosViewModel(
    listarVehiculos: ListarVehiculosUseCase,
    observarJornadas: ObservarJornadasUseCase,
    listarUsuarios: ListarUsuariosUseCase,
    listarZonas: ListarZonasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(VehiculosUiState())
    val uiState: StateFlow<VehiculosUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                combine(listarVehiculos(), observarJornadas(), listarUsuarios(), listarZonas()) { vehiculos, jornadas, usuarios, zonas ->
                    val porVehiculo = jornadas.groupBy { it.vehiculoId }
                    val nombre = usuarios.associate { it.id to it.nombres }
                    val zona = zonas.associate { it.id to it.nombre }
                    vehiculos.map { v ->
                        val abierta = porVehiculo[v.id].orEmpty().firstOrNull { it.estaAbierta }
                        VehiculoFila(v, abierta?.let { "${zona[it.zonaId] ?: "Ruta"} · ${nombre[it.usuarioId] ?: "Acopiador"}" }, porVehiculo[v.id]?.size ?: 0)
                    }.sortedWith(compareByDescending<VehiculoFila> { it.vehiculo.activo }.thenBy { it.vehiculo.nombre })
                }.flowOn(Dispatchers.Default).collect { filas -> _uiState.update { it.copy(cargando = false, vehiculos = filas) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar los vehículos.") } }
        }
    }
}

data class VehiculoFormUiState(
    val esEdicion: Boolean = false,
    val nombre: String = "",
    val placa: String = "",
    val activo: Boolean = true,
    val guardando: Boolean = false,
    val error: String? = null,
    val guardado: Boolean = false,
)

class VehiculoFormViewModel(
    private val id: String?,
    private val obtenerVehiculo: ObtenerVehiculoUseCase,
    private val crearVehiculo: CrearVehiculoUseCase,
    private val actualizarVehiculo: ActualizarVehiculoUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(VehiculoFormUiState(esEdicion = id != null))
    val uiState: StateFlow<VehiculoFormUiState> = _uiState.asStateFlow()

    init {
        if (id != null) viewModelScope.launch {
            cargaSegura { obtenerVehiculo(id) }.onSuccess { v -> v?.let { _uiState.update { s -> s.copy(nombre = it.nombre, placa = it.placa, activo = it.activo) } } }
        }
    }

    fun nombre(v: String) = _uiState.update { it.copy(nombre = v, error = null) }
    fun placa(v: String) = _uiState.update { it.copy(placa = v.uppercase().take(10), error = null) }
    fun activo(v: Boolean) = _uiState.update { it.copy(activo = v, error = null) }

    fun guardar() {
        val s = _uiState.value
        if (s.guardando) return
        if (s.nombre.isBlank() || s.placa.isBlank()) { _uiState.update { it.copy(error = "Escribe el nombre y la placa.") }; return }
        _uiState.update { it.copy(guardando = true, error = null) }
        viewModelScope.launch {
            val r = if (id == null) crearVehiculo(s.nombre, s.placa, s.activo).map { } else actualizarVehiculo(id, s.nombre, s.placa, s.activo)
            _uiState.update { it.copy(guardando = false, guardado = r.isSuccess, error = r.exceptionOrNull()?.message) }
        }
    }
}
