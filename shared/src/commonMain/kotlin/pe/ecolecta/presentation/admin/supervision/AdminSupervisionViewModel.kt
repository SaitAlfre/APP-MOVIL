package pe.ecolecta.presentation.admin.supervision

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.Job
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.presentation.cargaSegura

data class AdminSupervisionState(
    val cargando: Boolean = true,
    val error: String? = null,
    val controles: List<ControlCalidad> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val usuarios: List<Usuario> = emptyList(),
    val entregas: List<Entrega> = emptyList(),
) {
    fun nombre(c: ControlCalidad) = c.visita.proveedorNombre.ifBlank {
        proveedores.find { it.id == c.proveedorId }?.nombres ?: "Proveedor no disponible"
    }
    fun tecnico(c: ControlCalidad) = c.visita.tecnicoNombre.ifBlank {
        usuarios.find { it.id == c.usuarioId }?.nombres ?: "Técnico no disponible"
    }
    fun zonaId(c: ControlCalidad) = c.visita.zonaId.ifBlank {
        proveedores.find { it.id == c.proveedorId }?.zonaId.orEmpty()
    }
}

/** Consulta administrativa: no expone ninguna operación de escritura de análisis. */
class AdminSupervisionViewModel(
    private val calidad: ControlCalidadRepository,
    private val proveedores: ProveedorRepository,
    private val zonas: ZonaRepository,
    private val usuarios: UsuarioRepository,
    private val entregas: EntregaRepository,
) : ViewModel() {
    private val _state = MutableStateFlow(AdminSupervisionState())
    val state = _state.asStateFlow()
    private var carga: Job? = null
    init { cargar() }
    fun cargar() {
        carga?.cancel()
        carga = viewModelScope.launch {
            _state.update { it.copy(cargando = true, error = null) }
            cargaSegura {
                combine(calidad.observarTodos(), proveedores.observarTodos(), zonas.observarTodas(),
                    usuarios.observarTodos(), entregas.observarConFiltros()) { c, p, z, u, e ->
                    AdminSupervisionState(false, controles = c.filterNot { it.visita.ejemplo }.sortedByDescending { it.registradoEn },
                        proveedores = p, zonas = z, usuarios = u, entregas = e.sortedByDescending { it.registradoEn })
                }.collect { _state.value = it }
            }.onFailure { e -> _state.update { it.copy(cargando = false, error = e.message ?: "No se pudo cargar la información") } }
        }
    }
}

fun EstadoControlCalidad.etiquetaAdmin() = when (this) {
    EstadoControlCalidad.APROBADO -> "Aprobado"
    EstadoControlCalidad.OBSERVADO -> "Con observaciones"
    EstadoControlCalidad.RECHAZADO -> "Rechazado"
    EstadoControlCalidad.REPETIR -> "Repetir análisis"
}

fun filtrarControlesAdmin(
    datos: AdminSupervisionState, proveedorId: String?, zonaId: String?, estado: String?, busqueda: String, fecha: String,
): List<ControlCalidad> = datos.controles.filter { c ->
    !c.visita.ejemplo && (proveedorId == null || c.proveedorId == proveedorId) &&
        (zonaId == null || datos.zonaId(c) == zonaId) && (estado == null || c.estado.name == estado) &&
        (fecha.isBlank() || pe.ecolecta.presentation.design.formatearFecha(c.registradoEn) == fecha.trim()) &&
        (busqueda.isBlank() || listOf(datos.nombre(c), datos.tecnico(c), c.codigoMuestra,
            c.visita.proveedorCodigo, datos.proveedores.find { it.id == c.proveedorId }?.codigo.orEmpty())
            .any { it.contains(busqueda.trim(), ignoreCase = true) })
}
