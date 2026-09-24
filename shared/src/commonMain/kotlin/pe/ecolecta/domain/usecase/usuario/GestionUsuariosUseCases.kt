package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Proveedor
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
private val FORMATO_DOCUMENTO = Regex("^(\\d{8}|\\d{11})$")
private val FORMATO_CODIGO = Regex("^[A-Z0-9-]{3,20}$")

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
    /** Solo para un proveedor NUEVO: su ficha se crea junto con la cuenta. Excluye a [proveedorId]. */
    val fichaNueva: FichaNueva? = null,
)

/**
 * Datos operativos de la ficha de un proveedor nuevo (la finca o unidad que entrega leche). La
 * persona que inicia sesión es la de [DatosCuenta] y queda como responsable de la ficha.
 */
data class FichaNueva(
    val codigo: String,
    val nombre: String,
    val documento: String,
    val zonaId: String,
    val tachos: Int,
    val capacidadTachoL: Double,
    val telefono: String? = null,
    val direccion: String? = null,
)

/**
 * Reglas comunes a crear, editar y cambiar el estado de una cuenta:
 * - un solo rol por cuenta; una cuenta anterior con varios roles los conserva mientras no se cambien,
 *   y si se cambian debe quedar con uno solo (no se le quita nada sin que ADMIN lo elija);
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

    suspend fun validarZonaActiva(zonaId: String) {
        val zona = zonas.obtenerPorId(zonaId) ?: throw IllegalArgumentException("Selecciona la zona de la ficha.")
        require(zona.activo) { "La zona ${zona.nombre} está inactiva." }
    }

    suspend fun validarAsignaciones(
        usuarioId: String?,
        roles: Set<Rol>,
        zonaId: String?,
        proveedorId: String?,
        rolesAnteriores: Set<Rol> = emptySet(),
        creaFichaNueva: Boolean = false,
    ) {
        require(roles.isNotEmpty()) { "Selecciona un rol." }
        require(roles.size == 1 || roles == rolesAnteriores) {
            "Cada cuenta tiene un solo rol. Si una persona necesita otro perfil, créale una cuenta aparte."
        }
        require(Rol.PROVEEDOR !in roles || roles.size == 1) { "Una cuenta de proveedor no puede tener roles del personal." }
        if (Rol.CALIDAD in roles) require(zonaId != null) { "Asigna la zona del técnico de calidad." }
        if (zonaId != null) {
            val zona = zonas.obtenerPorId(zonaId) ?: throw IllegalArgumentException("La zona seleccionada ya no existe.")
            require(zona.activo) { "La zona ${zona.nombre} está inactiva." }
        }
        if (Rol.PROVEEDOR in roles && creaFichaNueva) {
            require(usuarioId == null && proveedorId == null) { "Una ficha nueva solo se crea junto con una cuenta nueva." }
        } else if (Rol.PROVEEDOR in roles) {
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
    private val proveedores: ProveedorRepository,
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
        val fichaNueva = datos.fichaNueva.takeIf { Rol.PROVEEDOR in datos.roles }
        val anterior = datos.id?.let { usuarios.obtenerPorId(it) ?: throw IllegalArgumentException("La cuenta ya no existe.") }
        reglas.validarAsignaciones(datos.id, datos.roles, zonaId, proveedorId, anterior?.roles?.toSet().orEmpty(), fichaNueva != null)
        val ahora = reloj.ahora().toEpochMilliseconds()

        if (anterior == null) return@runCatching crearCuenta(datos, username, nombres, dni, zonaId, proveedorId, fichaNueva, adminId, ahora)

        val id = run {
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
                accion = AccionAuditoria.ACTUALIZAR,
                valorAntes = "roles=${anterior.roles.joinToString { it.name }};activo=${anterior.activo}",
                valorDespues = "roles=${datos.roles.joinToString { it.name }};activo=${datos.activo};zona=${zonaId ?: "-"};proveedor=${proveedorId ?: "-"}" +
                    if (!datos.pin.isNullOrEmpty()) ";pin=restablecido" else "",
                motivo = null, usuarioId = adminId, ocurridoEn = ahora,
                deviceId = deviceIdProvider.obtenerId(), syncState = SyncState.PENDING,
            ),
        )
        id
    }

    /**
     * Alta: la cuenta, su zona, su ficha (nueva o existente libre) y la auditoría se guardan en una sola
     * transacción; si algo falla no queda una cuenta sin ficha ni una ficha sin cuenta.
     */
    private suspend fun crearCuenta(
        datos: DatosCuenta,
        username: String,
        nombres: String,
        dni: String,
        zonaId: String?,
        proveedorId: String?,
        fichaNueva: FichaNueva?,
        adminId: String,
        ahora: Long,
    ): String {
        require(FORMATO_USUARIO.matches(username)) { "El usuario debe tener de 3 a 30 caracteres: minúsculas, números, punto o guion bajo." }
        require(!usuarios.existeUsername(username, "")) { "Ya existe el usuario @$username." }
        val pin = datos.pin.orEmpty()
        require(FORMATO_PIN.matches(pin)) { "El PIN debe tener exactamente 4 dígitos." }
        require(pin == datos.confirmacionPin) { "La confirmación del PIN no coincide." }
        val hash = pinHasher.crearHash(pin)
        val usuario = Usuario.crear(
            id = nuevoId(), username = username, nombres = nombres, dni = dni, pinHash = hash.hash, pinSalt = hash.salt,
            activo = datos.activo, roles = datos.roles.toList(), updatedAt = ahora,
        ).getOrThrow()
        val ficha = fichaNueva?.let { crearFicha(it, usuario, ahora) }
        val deviceId = deviceIdProvider.obtenerId()
        val registros = buildList {
            add(
                Auditoria(
                    id = nuevoId(), entidad = "usuario", entidadId = usuario.id, accion = AccionAuditoria.CREAR, valorAntes = null,
                    valorDespues = "roles=${datos.roles.joinToString { it.name }};activo=${datos.activo};zona=${zonaId ?: "-"};" +
                        "proveedor=${ficha?.codigo ?: proveedorId ?: "-"}",
                    motivo = null, usuarioId = adminId, ocurridoEn = ahora, deviceId = deviceId, syncState = SyncState.PENDING,
                ),
            )
            if (ficha != null) add(
                Auditoria(
                    id = nuevoId(), entidad = "proveedor", entidadId = ficha.id, accion = AccionAuditoria.CREAR, valorAntes = null,
                    valorDespues = "codigo=${ficha.codigo};documento=${ficha.dni};zona=${ficha.zonaId};cuenta=${usuario.username}",
                    motivo = "Alta de proveedor nuevo con su cuenta", usuarioId = adminId, ocurridoEn = ahora,
                    deviceId = deviceId, syncState = SyncState.PENDING,
                ),
            )
        }
        cuentas.crearCuenta(usuario, zonaId, ficha, proveedorId, registros)
        return usuario.id
    }

    /** Ficha de un proveedor nuevo: código y documento únicos, zona activa y capacidad válida. */
    private suspend fun crearFicha(f: FichaNueva, titular: Usuario, ahora: Long): Proveedor {
        val codigo = f.codigo.trim().uppercase()
        val documento = f.documento.trim()
        val nombre = f.nombre.trim().replace(Regex("\\s+"), " ")
        require(FORMATO_CODIGO.matches(codigo)) { "El código debe tener de 3 a 20 caracteres: letras, números o guion." }
        require(nombre.length >= 3) { "Escribe el nombre del proveedor o finca." }
        require(FORMATO_DOCUMENTO.matches(documento)) { "El documento de la ficha debe ser un DNI de 8 dígitos o un RUC de 11." }
        require(!proveedores.existeCodigo(codigo, "")) { "Ya existe una ficha con el código $codigo." }
        require(!proveedores.existeDni(documento, "")) {
            "Ya existe una ficha con el documento $documento. Si es el mismo proveedor, usa «Ya existe su ficha»."
        }
        require(f.tachos in 1..99) { "La cantidad de tachos debe estar entre 1 y 99." }
        require(f.capacidadTachoL.isFinite() && f.capacidadTachoL > 0 && f.capacidadTachoL <= 200) {
            "La capacidad por tacho debe ser mayor a 0 y hasta 200 L."
        }
        reglas.validarZonaActiva(f.zonaId)
        return Proveedor.crear(
            id = nuevoId(), codigo = codigo, nombres = nombre, dni = documento,
            telefono = f.telefono?.trim()?.ifBlank { null }, direccion = f.direccion?.trim()?.ifBlank { null },
            zonaId = f.zonaId, tachos = f.tachos, capacidadTachoL = f.capacidadTachoL, updatedAt = ahora,
        ).getOrThrow().copy(dueno = titular.nombres, usuarioId = titular.id)
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
