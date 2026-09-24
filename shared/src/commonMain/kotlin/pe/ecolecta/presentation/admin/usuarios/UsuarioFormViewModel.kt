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
import pe.ecolecta.domain.usecase.usuario.FichaNueva
import pe.ecolecta.domain.usecase.usuario.DesbloquearCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.GuardarCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.ObtenerUsuarioUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

/** Cómo se da acceso a un proveedor: con una ficha nueva (lo habitual) o con una ficha que ya existe sin cuenta. */
enum class ModoProveedor { NUEVO, EXISTENTE }

data class UsuarioFormUiState(
    val usuarioId: String? = null,
    val esEdicion: Boolean = false,
    val cargando: Boolean = false,
    val guardando: Boolean = false,
    val username: String = "",
    val nombres: String = "",
    val dni: String = "",
    val roles: Set<Rol> = emptySet(),
    /** Roles guardados al abrir la cuenta; una cuenta anterior puede tener más de uno. */
    val rolesOriginales: Set<Rol> = emptySet(),
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
    val modoProveedor: ModoProveedor = ModoProveedor.NUEVO,
    /** Ficha vinculada al abrir la cuenta (en edición); cambiarla también pide confirmación. */
    val fichaOriginal: String? = null,
    val fichaCodigo: String = "",
    val fichaNombre: String = "",
    val fichaDocumento: String = "",
    val fichaZonaId: String? = null,
    val fichaTachos: String = "2",
    val fichaCapacidad: String = "40",
    val fichaTelefono: String = "",
    val fichaDireccion: String = "",
    /** Pide confirmar qué ficha existente verá esta cuenta antes de guardar. */
    val confirmandoVinculo: Boolean = false,
    val error: String? = null,
    val guardado: Boolean = false,
) {
    val esProveedor get() = Rol.PROVEEDOR in roles
    /** Solo una cuenta nueva puede crear su ficha; al editar se gestiona la ficha que ya tiene. */
    val creaFichaNueva get() = esProveedor && !esEdicion && modoProveedor == ModoProveedor.NUEVO
    val vinculaFichaExistente get() = esProveedor && !creaFichaNueva
    val fichaSeleccionada: Proveedor? get() = proveedores.firstOrNull { it.id == proveedorId }
    fun nombreZona(id: String?): String = zonas.firstOrNull { it.id == id }?.nombre ?: "zona no disponible"

    val necesitaZona get() = roles.any { it.requiereZona }
    val pideZonaObligatoria get() = Rol.CALIDAD in roles
    val pidePin get() = !esEdicion || restablecerPin
    /** Cuenta creada antes de la regla de un rol por cuenta: sus roles se conservan mientras ADMIN no elija uno. */
    val esCuentaMultirol get() = rolesOriginales.size > 1
    val conservaRolesOriginales get() = esCuentaMultirol && roles == rolesOriginales

    /** Fichas que esta cuenta puede tomar: libres o la que ya tiene. */
    val fichasLibres: List<Proveedor> get() = proveedores.filter { it.usuarioId == null || it.usuarioId == usuarioId }
    val fichasDisponibles: List<Proveedor> get() = fichasLibres
        .filter { busquedaFicha.isBlank() || "${it.codigo} ${it.nombres} ${it.dni}".contains(busquedaFicha.trim(), ignoreCase = true) }

    val faltante: String?
        get() = when {
            nombres.trim().length < 3 -> "Escribe los nombres y apellidos."
            dni.length != 8 -> "El DNI debe tener 8 dígitos."
            !esEdicion && username.trim().length < 3 -> "Escribe el nombre de usuario."
            roles.isEmpty() -> "Selecciona un rol."
            roles.size > 1 && !conservaRolesOriginales -> "Elige un solo rol para la cuenta."
            pideZonaObligatoria && zonaId == null -> "Asigna la zona del técnico de calidad."
            creaFichaNueva && fichaNombre.trim().length < 3 -> "Escribe el nombre del proveedor o finca."
            creaFichaNueva && fichaDocumento.length != 8 && fichaDocumento.length != 11 -> "El documento de la ficha debe ser un DNI de 8 dígitos o un RUC de 11."
            creaFichaNueva && fichaCodigo.trim().length < 3 -> "Escribe el código de la ficha."
            creaFichaNueva && fichaZonaId == null -> "Elige la zona de acopio de la ficha."
            creaFichaNueva && (fichaTachos.toIntOrNull() ?: 0) <= 0 -> "La cantidad de tachos debe ser mayor a 0."
            creaFichaNueva && (fichaCapacidad.replace(',', '.').toDoubleOrNull() ?: 0.0) <= 0.0 -> "La capacidad por tacho debe ser mayor a 0."
            vinculaFichaExistente && proveedorId == null -> "Elige la ficha existente que verá esta cuenta."
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
                        rolesOriginales = usuario.roles.toSet(),
                        activo = usuario.activo,
                        zonaId = cuentas.zonaAsignada(id),
                        proveedorId = ficha?.id,
                        fichaOriginal = ficha?.id,
                        modoProveedor = ModoProveedor.EXISTENTE,
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
        // Se abrió desde "Crear cuenta de acceso" de esa ficha: ADMIN ya eligió la ficha; igual se confirma al guardar.
        _uiState.update {
            it.copy(
                roles = setOf(Rol.PROVEEDOR),
                modoProveedor = ModoProveedor.EXISTENTE,
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
    fun modoProveedor(m: ModoProveedor) = _uiState.update {
        // Cambiar de camino nunca arrastra una ficha elegida antes: en «Ya existe su ficha» se elige a mano.
        it.copy(modoProveedor = m, proveedorId = if (m == ModoProveedor.EXISTENTE) it.proveedorId.takeIf { _ -> it.esEdicion } else null, error = null)
    }
    fun fichaCodigo(v: String) = _uiState.update { it.copy(fichaCodigo = v.uppercase().filter { c -> c.isLetterOrDigit() || c == '-' }.take(20), error = null) }
    fun fichaNombre(v: String) = _uiState.update { it.copy(fichaNombre = v.take(80), error = null) }
    fun fichaDocumento(v: String) { if (v.length <= 11 && v.all(Char::isDigit)) _uiState.update { it.copy(fichaDocumento = v, error = null) } }
    fun fichaZona(v: String) = _uiState.update { it.copy(fichaZonaId = v, error = null) }
    fun fichaTachos(v: String) { if (v.length <= 2 && v.all(Char::isDigit)) _uiState.update { it.copy(fichaTachos = v, error = null) } }
    fun fichaCapacidad(v: String) { if (v.length <= 5 && v.all { it.isDigit() || it == '.' || it == ',' }) _uiState.update { it.copy(fichaCapacidad = v, error = null) } }
    fun fichaTelefono(v: String) { if (v.length <= 12 && v.all { it.isDigit() || it == '+' }) _uiState.update { it.copy(fichaTelefono = v) } }
    fun fichaDireccion(v: String) = _uiState.update { it.copy(fichaDireccion = v.take(120)) }
    fun cancelarVinculo() = _uiState.update { it.copy(confirmandoVinculo = false) }
    fun restablecerPin(v: Boolean) = _uiState.update { it.copy(restablecerPin = v, pin = "", confirmacionPin = "") }
    fun pin(v: String) { if (v.length <= 4 && v.all(Char::isDigit)) _uiState.update { it.copy(pin = v, error = null) } }
    fun confirmacion(v: String) { if (v.length <= 4 && v.all(Char::isDigit)) _uiState.update { it.copy(confirmacionPin = v, error = null) } }

    /** Un rol por cuenta: elegir uno reemplaza al anterior (también en una cuenta antigua con varios). */
    fun elegirRol(rol: Rol) = aplicarRoles(setOf(rol))

    /** Vuelve a dejar la cuenta antigua con los roles que ya tenía, sin cambios. */
    fun conservarRolesOriginales() = aplicarRoles(_uiState.value.rolesOriginales)

    private fun aplicarRoles(roles: Set<Rol>) = _uiState.update { s ->
        val codigoSugerido = s.fichaCodigo.ifBlank { siguienteCodigo(s.proveedores.map { it.codigo }) }
        s.copy(
            fichaCodigo = if (Rol.PROVEEDOR in roles) codigoSugerido else s.fichaCodigo,
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

    /** Guarda; si la cuenta tomará una ficha existente distinta de la que ya tenía, primero pide confirmarlo. */
    fun guardar() = ejecutarGuardado(confirmado = false)

    fun confirmarVinculo() = ejecutarGuardado(confirmado = true)

    private fun ejecutarGuardado(confirmado: Boolean) {
        val s = _uiState.value
        if (s.guardando) return
        s.faltante?.let { falta -> _uiState.update { it.copy(error = falta, confirmandoVinculo = false) }; return }
        if (s.vinculaFichaExistente && s.proveedorId != s.fichaOriginal && !confirmado) {
            _uiState.update { it.copy(confirmandoVinculo = true, error = null) }
            return
        }
        val fichaNueva = if (s.creaFichaNueva) FichaNueva(
            codigo = s.fichaCodigo, nombre = s.fichaNombre, documento = s.fichaDocumento, zonaId = s.fichaZonaId.orEmpty(),
            tachos = s.fichaTachos.toInt(), capacidadTachoL = s.fichaCapacidad.replace(',', '.').toDouble(),
            telefono = s.fichaTelefono, direccion = s.fichaDireccion,
        ) else null
        _uiState.update { it.copy(guardando = true, error = null, confirmandoVinculo = false) }
        viewModelScope.launch {
            val adminId = obtenerSesion().first()?.usuario?.id
            if (adminId == null) {
                _uiState.update { it.copy(guardando = false, error = "Tu sesión ya no está activa.") }
                return@launch
            }
            val datos = DatosCuenta(
                id = id, username = s.username, nombres = s.nombres, dni = s.dni, roles = s.roles, activo = s.activo,
                zonaId = s.zonaId, proveedorId = s.proveedorId.takeIf { s.vinculaFichaExistente }, fichaNueva = fichaNueva,
                pin = s.pin.takeIf { s.pidePin }, confirmacionPin = s.confirmacionPin.takeIf { s.pidePin },
            )
            guardarCuenta(datos, adminId).fold(
                onSuccess = { _uiState.update { it.copy(guardando = false, guardado = true) } },
                onFailure = { e -> _uiState.update { it.copy(guardando = false, error = e.message ?: "No se pudo guardar la cuenta.") } },
            )
        }
    }
}

/** Siguiente código libre con el formato PRV-NNN de las fichas actuales; ADMIN puede cambiarlo. */
internal fun siguienteCodigo(codigos: List<String>): String {
    val mayor = codigos.mapNotNull { Regex("^PRV-(\\d+)$").find(it)?.groupValues?.get(1)?.toIntOrNull() }.maxOrNull() ?: 0
    return "PRV-${(mayor + 1).toString().padStart(3, '0')}"
}
