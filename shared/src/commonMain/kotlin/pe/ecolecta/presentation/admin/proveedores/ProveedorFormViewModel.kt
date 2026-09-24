package pe.ecolecta.presentation.admin.proveedores

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.usecase.proveedor.ActualizarProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorUseCase
import pe.ecolecta.domain.usecase.usuario.ListarUsuariosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

/** Edición de una ficha existente; el alta se hace junto con su cuenta en Usuarios y roles. */
data class ProveedorFormUiState(
    val codigo: String = "",
    val nombres: String = "",
    val dueno: String = "",
    val dni: String = "",
    val telefono: String = "",
    val direccion: String = "",
    val zonaId: String = "",
    val tachos: String = "1",
    val capacidadTachoL: String = "40",
    val estado: EstadoProveedor = EstadoProveedor.ACTIVO,
    val zonas: List<Zona> = emptyList(),
    /** Cuenta del portal vinculada a esta ficha; se gestiona en Usuarios y roles. */
    val cuenta: Usuario? = null,
    val guardando: Boolean = false,
    val error: String? = null,
    val guardado: Boolean = false,
) {
    val faltante: String?
        get() = when {
            codigo.isBlank() -> "Escribe el código del proveedor."
            nombres.isBlank() -> "Escribe el nombre del proveedor o finca."
            dni.isBlank() -> "Escribe el DNI o RUC."
            zonaId.isBlank() -> "Selecciona la zona."
            (tachos.toIntOrNull() ?: 0) <= 0 -> "La cantidad de tachos debe ser mayor a 0."
            (capacidadTachoL.replace(',', '.').toDoubleOrNull() ?: 0.0) <= 0.0 -> "La capacidad por tacho debe ser mayor a 0."
            else -> null
        }
}

class ProveedorFormViewModel(
    private val id: String,
    private val obtenerProveedor: ObtenerProveedorUseCase,
    private val actualizarProveedor: ActualizarProveedorUseCase,
    private val listarZonas: ListarZonasUseCase,
    private val listarUsuarios: ListarUsuariosUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProveedorFormUiState())
    val uiState: StateFlow<ProveedorFormUiState> = _uiState.asStateFlow()
    private var usuarioVinculado: String? = null
    private var usuarios: List<Usuario> = emptyList()

    init {
        viewModelScope.launch {
            cargaSegura {
                combine(listarZonas(soloActivas = true), listarUsuarios()) { z, u -> z to u }.collect { (zonas, lista) ->
                    usuarios = lista
                    _uiState.update { s ->
                        s.copy(
                            zonas = zonas,
                            zonaId = s.zonaId.ifBlank { zonas.firstOrNull()?.id.orEmpty() },
                            cuenta = usuarios.firstOrNull { it.id == usuarioVinculado },
                        )
                    }
                }
            }
        }
        viewModelScope.launch {
            cargaSegura { obtenerProveedor(id) }.onSuccess { p ->
                if (p == null) return@onSuccess
                usuarioVinculado = p.usuarioId
                _uiState.update {
                    it.copy(
                        codigo = p.codigo, nombres = p.nombres, dueno = p.dueno.orEmpty(), dni = p.dni,
                        telefono = p.telefono.orEmpty(), direccion = p.direccion.orEmpty(), zonaId = p.zonaId,
                        tachos = p.tachos.toString(), capacidadTachoL = p.capacidadTachoL.toString().removeSuffix(".0"), estado = p.estado,
                        cuenta = usuarios.firstOrNull { u -> u.id == p.usuarioId },
                    )
                }
            }.onFailure { e -> _uiState.update { it.copy(error = e.message) } }
        }
    }

    fun codigo(v: String) = _uiState.update { it.copy(codigo = v.uppercase(), error = null) }
    fun nombres(v: String) = _uiState.update { it.copy(nombres = v, error = null) }
    fun dueno(v: String) = _uiState.update { it.copy(dueno = v) }
    fun dni(v: String) { if (v.length <= 11 && v.all(Char::isDigit)) _uiState.update { it.copy(dni = v, error = null) } }
    fun telefono(v: String) { if (v.length <= 12 && v.all { it.isDigit() || it == '+' }) _uiState.update { it.copy(telefono = v) } }
    fun direccion(v: String) = _uiState.update { it.copy(direccion = v) }
    fun zona(v: String) = _uiState.update { it.copy(zonaId = v, error = null) }
    fun tachos(v: String) { if (v.length <= 2 && v.all(Char::isDigit)) _uiState.update { it.copy(tachos = v, error = null) } }
    fun capacidad(v: String) { if (v.length <= 5) _uiState.update { it.copy(capacidadTachoL = v, error = null) } }
    fun estado(v: EstadoProveedor) = _uiState.update { it.copy(estado = v) }

    fun guardar() {
        val s = _uiState.value
        if (s.guardando) return
        s.faltante?.let { f -> _uiState.update { it.copy(error = f) }; return }
        val tachos = s.tachos.toInt()
        val capacidad = s.capacidadTachoL.replace(',', '.').toDouble()
        _uiState.update { it.copy(guardando = true, error = null) }
        viewModelScope.launch {
            val r = actualizarProveedor(
                id, s.codigo, s.nombres, s.dni, s.telefono.ifBlank { null }, s.direccion.ifBlank { null }, s.zonaId, tachos, capacidad, s.estado, s.dueno,
            )
            _uiState.update { it.copy(guardando = false, guardado = r.isSuccess, error = r.exceptionOrNull()?.message) }
        }
    }
}
