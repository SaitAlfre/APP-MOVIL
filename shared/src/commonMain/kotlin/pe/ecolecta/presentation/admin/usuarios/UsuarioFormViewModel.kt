package pe.ecolecta.presentation.admin.usuarios

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.usuario.DatosCuenta
import pe.ecolecta.domain.usecase.usuario.DesbloquearCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.GuardarCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.ObtenerUsuarioUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

data class UsuarioFormUiState(
    val usuarioId: String? = null,
    val esEdicion: Boolean = false,
    val cargando: Boolean = false,
    val guardando: Boolean = false,
    val username: String = "",
    val nombres: String = "",
    val dni: String = "",
    val roles: Set<Rol> = emptySet(),
    val activo: Boolean = true,
    val zonaId: String? = null,
    val proveedorId: String? = null,
    val restablecerPin: Boolean = false,
    val pin: String = "",
    val confirmacionPin: String = "",
    val bloqueada: Boolean = false,
    val esMiCuenta: Boolean = false,
    val zonas: List<Zona> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val busquedaFicha: String = "",
    val error: String? = null,
    val guardado: Boolean = false,
) {
    val necesitaZona get() = roles.any { it.requiereZona }
    val pideZonaObligatoria get() = Rol.CALIDAD in roles
    val pidePin get() = !esEdicion || restablecerPin

    /** Fichas que esta cuenta puede tomar: libres o la que ya tiene. */
    val fichasDisponibles: List<Proveedor> get() = proveedores
        .filter { it.usuarioId == null || it.usuarioId == usuarioId }
        .filter { busquedaFicha.isBlank() || "${it.codigo} ${it.nombres} ${it.dni}".contains(busquedaFicha.trim(), ignoreCase = true) }

    val faltante: String?
        get() = when {
            nombres.trim().length < 3 -> "Escribe los nombres y apellidos."
            dni.length != 8 -> "El DNI debe tener 8 dígitos."
            !esEdicion && username.trim().length < 3 -> "Escribe el nombre de usuario."
            roles.isEmpty() -> "Selecciona al menos un rol."
            pideZonaObligatoria && zonaId == null -> "Asigna la zona del técnico de calidad."
            Rol.PROVEEDOR in roles && proveedorId == null -> "Vincula la ficha del proveedor."
            pidePin && pin.length != 4 -> "El PIN debe tener 4 dígitos."
            pidePin && pin != confirmacionPin -> "La confirmación del PIN no coincide."
            else -> null
        }
}

class UsuarioFormViewModel(
    private val id: String?,
    private val fichaId: String?,
    private val obtenerUsuario: ObtenerUsuarioUseCase,
    private val cuentas: CuentasRepository,
    private val listarZonas: ListarZonasUseCase,
    private val listarProveedores: ListarProveedoresUseCase,
    private val guardarCuenta: GuardarCuentaUseCase,
    private val desbloquearCuenta: DesbloquearCuentaUseCase,
    private val obtenerSesion: ObtenerSesionUseCase,
    private val reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(UsuarioFormUiState(usuarioId = id, esEdicion = id != null, cargando = id != null))
    val uiState: StateFlow<UsuarioFormUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            cargaSegura { listarZonas(soloActivas = true).collect { z -> _uiState.update { it.copy(zonas = z) } } }
        }
        viewModelScope.launch {
            cargaSegura {
                listarProveedores().collect { p ->
                    _uiState.update { it.copy(proveedores = p.sortedBy { x -> x.codigo }) }
                    prellenarDesdeFicha()
                }
            }
        }
        if (id != null) viewModelScope.launch {
            cargaSegura {
                val usuario = obtenerUsuario(id) ?: error("La cuenta ya no existe.")
                val adminId = obtenerSesion().first()?.usuario?.id
                val ficha = listarProveedores().first().firstOrNull { it.usuarioId == id }
                _uiState.update {
                    it.copy(
                        cargando = false,
                        username = usuario.username,
                        nombres = usuario.nombres,
                        dni = usuario.dni,
                        roles = usuario.roles.toSet(),
                        activo = usuario.activo,
                        zonaId = cuentas.zonaAsignada(id),
                        proveedorId = ficha?.id,
                        bloqueada = (usuario.bloqueadoHasta ?: 0) > reloj.ahora().toEpochMilliseconds(),
                        esMiCuenta = usuario.id == adminId,
                    )
                }
            }.onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message) } }
        }
    }

    /** Cuenta nueva abierta desde una ficha sin acceso: rol, ficha, nombre, DNI y usuario sugeridos. */
    private var prellenado = false
    private fun prellenarDesdeFicha() {
        if (prellenado || id != null || fichaId == null) return
        val ficha = _uiState.value.proveedores.firstOrNull { it.id == fichaId } ?: return
        prellenado = true
        _uiState.update {
            it.copy(
                roles = setOf(Rol.PROVEEDOR),
                proveedorId = ficha.id,
                nombres = ficha.dueno ?: ficha.nombres,
                dni = ficha.dni.takeIf { d -> d.length == 8 }.orEmpty(),
                username = ficha.codigo.lowercase().replace('-', '_').filter { c -> c.isLetterOrDigit() || c == '_' || c == '.' }.take(30),
            )
        }
    }

    fun nombres(v: String) = _uiState.update { it.copy(nombres = v, error = null) }
    fun dni(v: String) { if (v.length <= 8 && v.all(Char::isDigit)) _uiState.update { it.copy(dni = v, error = null) } }
    fun username(v: String) = _uiState.update { it.copy(username = v.lowercase().filter { c -> c.isLetterOrDigit() || c == '.' || c == '_' }.take(30), error = null) }
    fun activo(v: Boolean) = _uiState.update { it.copy(activo = v, error = null) }
    fun zona(zonaId: String?) = _uiState.update { it.copy(zonaId = zonaId, error = null) }
    fun ficha(proveedorId: String) = _uiState.update { it.copy(proveedorId = proveedorId, error = null) }
    fun buscarFicha(v: String) = _uiState.update { it.copy(busquedaFicha = v) }
    fun restablecerPin(v: Boolean) = _uiState.update { it.copy(restablecerPin = v, pin = "", confirmacionPin = "") }
    fun pin(v: String) { if (v.length <= 4 && v.all(Char::isDigit)) _uiState.update { it.copy(pin = v, error = null) } }
    fun confirmacion(v: String) { if (v.length <= 4 && v.all(Char::isDigit)) _uiState.update { it.copy(confirmacionPin = v, error = null) } }

    /** Proveedor es un perfil exclusivo: elegirlo quita los del personal y viceversa. */
    fun alternarRol(rol: Rol) = _uiState.update { s ->
        val roles = when {
            rol in s.roles -> s.roles - rol
            rol == Rol.PROVEEDOR -> setOf(Rol.PROVEEDOR)
            else -> s.roles - Rol.PROVEEDOR + rol
        }
        s.copy(
            roles = roles,
            zonaId = s.zonaId.takeIf { roles.any { r -> r.requiereZona } },
            proveedorId = s.proveedorId.takeIf { Rol.PROVEEDOR in roles },
            error = null,
        )
    }

    fun desbloquear() {
        val cuenta = id ?: return
        viewModelScope.launch {
            desbloquearCuenta(cuenta).fold(
                onSuccess = { _uiState.update { it.copy(bloqueada = false) } },
                onFailure = { e -> _uiState.update { it.copy(error = e.message) } },
            )
        }
    }

    fun guardar() {
        val s = _uiState.value
        if (s.guardando) return
        s.faltante?.let { falta -> _uiState.update { it.copy(error = falta) }; return }
        _uiState.update { it.copy(guardando = true, error = null) }
        viewModelScope.launch {
            val adminId = obtenerSesion().first()?.usuario?.id
            if (adminId == null) {
                _uiState.update { it.copy(guardando = false, error = "Tu sesión ya no está activa.") }
                return@launch
            }
            val datos = DatosCuenta(
                id = id, username = s.username, nombres = s.nombres, dni = s.dni, roles = s.roles, activo = s.activo,
                zonaId = s.zonaId, proveedorId = s.proveedorId,
                pin = s.pin.takeIf { s.pidePin }, confirmacionPin = s.confirmacionPin.takeIf { s.pidePin },
            )
            guardarCuenta(datos, adminId).fold(
                onSuccess = { _uiState.update { it.copy(guardando = false, guardado = true) } },
                onFailure = { e -> _uiState.update { it.copy(guardando = false, error = e.message ?: "No se pudo guardar la cuenta.") } },
            )
        }
    }
}
