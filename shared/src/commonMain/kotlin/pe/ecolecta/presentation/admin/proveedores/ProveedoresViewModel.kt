package pe.ecolecta.presentation.admin.proveedores

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
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

/** Ficha con el nombre de su zona y el estado de su cuenta de acceso ya resueltos. */
data class ProveedorFila(val proveedor: Proveedor, val zona: String, val cuenta: String?, val cuentaActiva: Boolean)

data class ProveedoresUiState(
    val cargando: Boolean = true,
    val filas: List<ProveedorFila> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val texto: String = "",
    val zonaId: String? = null,
    val estado: EstadoProveedor? = EstadoProveedor.ACTIVO,
    val sinCuenta: Boolean = false,
    val error: String? = null,
) {
    val visibles: List<ProveedorFila> = filas.filter { f ->
        val p = f.proveedor
        (texto.isBlank() || listOf(p.codigo, p.nombres, p.dni, p.dueno.orEmpty()).any { it.contains(texto.trim(), ignoreCase = true) }) &&
            (zonaId == null || p.zonaId == zonaId) &&
            (estado == null || p.estado == estado) &&
            (!sinCuenta || f.cuenta == null)
    }
}

class ProveedoresViewModel(
    listarProveedores: ListarProveedoresUseCase,
    listarZonas: ListarZonasUseCase,
    listarUsuarios: ListarUsuariosUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProveedoresUiState())
    val uiState: StateFlow<ProveedoresUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura {
                combine(listarProveedores(), listarZonas(), listarUsuarios()) { provs, zonas, usuarios ->
                    val zona = zonas.associate { it.id to it.nombre }
                    val usuario = usuarios.associateBy { it.id }
                    zonas to provs.map { p ->
                        val cuenta = p.usuarioId?.let { usuario[it] }
                        ProveedorFila(p, zona[p.zonaId] ?: "Sin zona", cuenta?.username, cuenta?.activo == true)
                    }
                }.flowOn(Dispatchers.Default).collect { (zonas, filas) -> _uiState.update { it.copy(cargando = false, zonas = zonas, filas = filas) } }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron cargar los proveedores.") } }
        }
    }

    fun buscar(v: String) = _uiState.update { it.copy(texto = v) }
    fun zona(id: String?) = _uiState.update { it.copy(zonaId = id) }
    fun estado(e: EstadoProveedor?) = _uiState.update { it.copy(estado = e) }
    fun sinCuenta(v: Boolean) = _uiState.update { it.copy(sinCuenta = v) }
}
