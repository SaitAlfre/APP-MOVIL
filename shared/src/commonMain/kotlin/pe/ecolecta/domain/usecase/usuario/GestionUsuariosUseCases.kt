package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.security.PinHasher

private val FORMATO_USUARIO = Regex("^[a-z0-9._]{3,30}$")
private val FORMATO_DNI = Regex("^\\d{8}$")
private val FORMATO_PIN = Regex("^\\d{4}$")

/** Lo que el formulario de Usuarios y roles envía; [id] nulo significa cuenta nueva. */
data class DatosCuenta(
    val id: String?,
    val username: String,
    val nombres: String,
    val dni: String,
    val roles: Set<Rol>,
    val activo: Boolean,
    val zonaId: String?,
    val proveedorId: String?,
    val pin: String?,
    val confirmacionPin: String?,
)

/**
 * Reglas comunes a crear, editar y cambiar el estado de una cuenta:
 * - un proveedor no comparte cuenta con personal de la planta y siempre tiene una ficha vinculada;
 * - el técnico de calidad necesita una zona activa (el acopiador puede tenerla como sugerida);
 * - nadie se desactiva ni se quita el rol de administrador a sí mismo, y siempre queda un administrador activo;
 * - un acopiador con una jornada abierta no puede quedar inactivo ni perder el rol.
 */
class ReglasCuenta(
    private val usuarios: UsuarioRepository,
    private val zonas: ZonaRepository,
    private val proveedores: ProveedorRepository,
    private val jornadas: JornadaRepository,
) {
    suspend fun validarCambioDeAcceso(anterior: Usuario, rolesNuevos: Set<Rol>, activoNuevo: Boolean, adminId: String) {
        val pierdeAdmin = Rol.ADMIN in anterior.roles && anterior.activo && (Rol.ADMIN !in rolesNuevos || !activoNuevo)
        if (anterior.id == adminId) {
            require(activoNuevo) { "No puedes desactivar tu propia cuenta." }
            require(Rol.ADMIN in rolesNuevos) { "No puedes quitarte el rol de administrador." }
        }
        if (pierdeAdmin) {
            require(usuarios.contarUsuariosConRol(Rol.ADMIN) > 1) { "Debe quedar al menos un administrador activo." }
        }
        val pierdeAcopio = Rol.ACOPIADOR in anterior.roles && (Rol.ACOPIADOR !in rolesNuevos || !activoNuevo)
        if (pierdeAcopio) {
            require(jornadas.obtenerAbiertaPorUsuario(anterior.id) == null) {
                "${anterior.nombres} tiene una jornada abierta. Debe cerrarla antes de desactivar la cuenta o quitarle el rol de acopiador."
            }
        }
    }

    suspend fun validarAsignaciones(usuarioId: String?, roles: Set<Rol>, zonaId: String?, proveedorId: String?) {
        require(roles.isNotEmpty()) { "Selecciona al menos un rol." }
        require(Rol.PROVEEDOR !in roles || roles.size == 1) { "Una cuenta de proveedor no puede tener roles del personal." }
        if (Rol.CALIDAD in roles) require(zonaId != null) { "Asigna la zona del técnico de calidad." }
        if (zonaId != null) {
            val zona = zonas.obtenerPorId(zonaId) ?: throw IllegalArgumentException("La zona seleccionada ya no existe.")
            require(zona.activo) { "La zona ${zona.nombre} está inactiva." }
        }
        if (Rol.PROVEEDOR in roles) {
            val id = proveedorId ?: throw IllegalArgumentException("Vincula la cuenta a una ficha de proveedor.")
            val ficha = proveedores.obtenerPorId(id) ?: throw IllegalArgumentException("La ficha de proveedor ya no existe.")
            require(ficha.usuarioId == null || ficha.usuarioId == usuarioId) { "La ficha ${ficha.codigo} ya está vinculada a otra cuenta." }
        }
    }
}

/** Crea o actualiza una cuenta con sus roles, zona, ficha de proveedor y PIN, dejando auditoría. */
class GuardarCuentaUseCase(
    private val usuarios: UsuarioRepository,
    private val cuentas: CuentasRepository,
    private val reglas: ReglasCuenta,
    private val crearUsuario: CrearUsuarioUseCase,
    private val auditoria: AuditoriaRepository,
    private val pinHasher: PinHasher,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(datos: DatosCuenta, adminId: String): Result<String> = runCatching {
        val username = datos.username.trim().lowercase()
        val nombres = datos.nombres.trim().replace(Regex("\\s+"), " ")
        val dni = datos.dni.trim()
        require(nombres.length >= 3) { "Escribe los nombres y apellidos completos." }
        require(FORMATO_DNI.matches(dni)) { "El DNI debe tener 8 dígitos." }
        require(!usuarios.existeDni(dni, datos.id.orEmpty())) { "Ya existe una cuenta con el DNI $dni." }

        val zonaId = datos.zonaId.takeIf { datos.roles.any { it.requiereZona } }
        val proveedorId = datos.proveedorId.takeIf { Rol.PROVEEDOR in datos.roles }
        reglas.validarAsignaciones(datos.id, datos.roles, zonaId, proveedorId)
        val ahora = reloj.ahora().toEpochMilliseconds()

        val id = if (datos.id == null) {
            require(FORMATO_USUARIO.matches(username)) { "El usuario debe tener de 3 a 30 caracteres: minúsculas, números, punto o guion bajo." }
            crearUsuario(username, nombres, dni, datos.pin.orEmpty(), datos.confirmacionPin.orEmpty(), datos.roles.toList(), datos.activo)
                .getOrThrow().id
        } else {
            val anterior = usuarios.obtenerPorId(datos.id) ?: throw IllegalArgumentException("La cuenta ya no existe.")
            reglas.validarCambioDeAcceso(anterior, datos.roles, datos.activo, adminId)
            val nuevoPin = datos.pin?.takeIf { it.isNotEmpty() }?.let { pin ->
                require(FORMATO_PIN.matches(pin)) { "El PIN debe tener exactamente 4 dígitos." }
                require(pin == datos.confirmacionPin) { "La confirmación del PIN no coincide." }
                pinHasher.crearHash(pin)
            }
            val rolesAnteriores = anterior.roles.toSet()
            usuarios.actualizarCompleto(
                id = anterior.id,
                nombres = nombres,
                dni = dni,
                activo = datos.activo,
                rolesAgregados = (datos.roles - rolesAnteriores).toList(),
                rolesQuitados = (rolesAnteriores - datos.roles).toList(),
                nuevoPin = nuevoPin,
                updatedAt = ahora,
            )
            anterior.id
        }
        cuentas.guardarAsignaciones(id, zonaId, proveedorId)
        auditoria.insertar(
            Auditoria(
                id = nuevoId(), entidad = "usuario", entidadId = id,
                accion = if (datos.id == null) AccionAuditoria.CREAR else AccionAuditoria.ACTUALIZAR,
                valorAntes = null,
                valorDespues = "roles=${datos.roles.joinToString { it.name }};activo=${datos.activo};zona=${zonaId ?: "-"};proveedor=${proveedorId ?: "-"}" +
                    if (datos.id != null && !datos.pin.isNullOrEmpty()) ";pin=restablecido" else "",
                motivo = null, usuarioId = adminId, ocurridoEn = ahora,
                deviceId = deviceIdProvider.obtenerId(), syncState = SyncState.PENDING,
            ),
        )
        id
    }
}

/** Activa o desactiva una cuenta sin pasar por el formulario completo. */
class CambiarEstadoCuentaUseCase(
    private val usuarios: UsuarioRepository,
    private val reglas: ReglasCuenta,
    private val auditoria: AuditoriaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(id: String, activo: Boolean, adminId: String): Result<Unit> = runCatching {
        val usuario = usuarios.obtenerPorId(id) ?: throw IllegalArgumentException("La cuenta ya no existe.")
        if (usuario.activo == activo) return@runCatching
        reglas.validarCambioDeAcceso(usuario, usuario.roles.toSet(), activo, adminId)
        val ahora = reloj.ahora().toEpochMilliseconds()
        usuarios.actualizar(id, usuario.nombres, usuario.dni, activo, ahora)
        auditoria.insertar(
            Auditoria(
                id = nuevoId(), entidad = "usuario", entidadId = id,
                accion = if (activo) AccionAuditoria.ACTUALIZAR else AccionAuditoria.DESACTIVAR,
                valorAntes = "activo=${usuario.activo}", valorDespues = "activo=$activo", motivo = null,
                usuarioId = adminId, ocurridoEn = ahora, deviceId = deviceIdProvider.obtenerId(), syncState = SyncState.PENDING,
            ),
        )
    }
}

/** Quita el bloqueo temporal por PIN incorrecto. */
class DesbloquearCuentaUseCase(private val usuarios: UsuarioRepository, private val reloj: Reloj) {
    suspend operator fun invoke(id: String): Result<Unit> = runCatching {
        usuarios.resetIntentos(id, reloj.ahora().toEpochMilliseconds())
    }
}
