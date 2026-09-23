package pe.ecolecta.presentation.admin.zonas

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
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.usecase.jornada.ObservarJornadasUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.zona.ActualizarZonaUseCase
import pe.ecolecta.domain.usecase.zona.CrearZonaUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.domain.usecase.zona.ObtenerZonaUseCase
import pe.ecolecta.presentation.cargaSegura

/** Una zona con todo lo que depende de ella: fichas, personal asignado y jornada en curso. */
data class ZonaFila(
    val zona: Zona,
    val proveedoresActivos: Int,
    val acopiadores: List<String>,
    val tecnicos: List<String>,
    val jornadaAbiertaPor: String?,
) {
    val enUso get() = proveedoresActivos > 0 || acopiadores.isNotEmpty() || tecnicos.isNotEmpty() || jornadaAbiertaPor != null
}

data class ZonasUiState(val cargando: Boolean = true, val zonas: List<ZonaFila> = emptyList(), val error: String? = null)

class ZonasViewModel(
    listarZonas: ListarZonasUseCase,
    listarProveedores: ListarProveedoresUseCase,
    listarUsuarios: ListarUsuariosUseCase,
    observarJornadas: ObservarJornadasUseCase,
    cuentas: CuentasRepository,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ZonasUiState())
    val uiState: StateFlow<ZonasUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                combine(listarZonas(), listarProveedores(), listarUsuarios(), cuentas.observarZonasAsignadas(), observarJornadas()) { zonas, provs, usuarios, asignadas, jornadas ->
                    val activos = provs.filter { it.estado == EstadoProveedor.ACTIVO }.groupingBy { it.zonaId }.eachCount()
                    val abiertas = jornadas.filter { it.estaAbierta }.associateBy { it.zonaId }
                    val nombre = usuarios.associate { it.id to it.nombres }
                    zonas.map { z ->
                        val deZona = usuarios.filter { u -> u.activo && asignadas[u.id] == z.id }
                        ZonaFila(
                            zona = z,
                            proveedoresActivos = activos[z.id] ?: 0,
                            acopiadores = deZona.filter { Rol.ACOPIADOR in it.roles }.map { it.nombres },
                            tecnicos = deZona.filter { Rol.CALIDAD in it.roles }.map { it.nombres },
                            jornadaAbiertaPor = abiertas[z.id]?.let { nombre[it.usuarioId] ?: "un acopiador" },
                        )
                    }.sortedWith(compareByDescending<ZonaFila> { it.zona.activo }.thenBy { it.zona.nombre })
                }.flowOn(Dispatchers.Default).collect { filas -> _uiState.update { it.copy(cargando = false, zonas = filas) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar las zonas.") } }
        }
    }
}

data class ZonaFormUiState(
    val esEdicion: Boolean = false,
    val nombre: String = "",
    val activo: Boolean = true,
    val guardando: Boolean = false,
    val error: String? = null,
    val guardado: Boolean = false,
)

class ZonaFormViewModel(
    private val id: String?,
    private val obtenerZona: ObtenerZonaUseCase,
    private val crearZona: CrearZonaUseCase,
    private val actualizarZona: ActualizarZonaUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ZonaFormUiState(esEdicion = id != null))
    val uiState: StateFlow<ZonaFormUiState> = _uiState.asStateFlow()

    init {
        if (id != null) viewModelScope.launch {
            cargaSegura { obtenerZona(id) }.onSuccess { z -> z?.let { _uiState.update { s -> s.copy(nombre = it.nombre, activo = it.activo) } } }
        }
    }

    fun nombre(v: String) = _uiState.update { it.copy(nombre = v.uppercase(), error = null) }
    fun activo(v: Boolean) = _uiState.update { it.copy(activo = v, error = null) }

    fun guardar() {
        val s = _uiState.value
        if (s.guardando) return
        if (s.nombre.isBlank()) { _uiState.update { it.copy(error = "Escribe el nombre de la zona.") }; return }
        _uiState.update { it.copy(guardando = true, error = null) }
        viewModelScope.launch {
            val r = if (id == null) crearZona(s.nombre, s.activo).map { } else actualizarZona(id, s.nombre, s.activo)
            _uiState.update { it.copy(guardando = false, guardado = r.isSuccess, error = r.exceptionOrNull()?.message) }
        }
    }
}
