package pe.ecolecta.data.repository

import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.EntidadCambio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.local.tieneCambioPendiente
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.ComunicadoServidor
import pe.ecolecta.domain.repository.ControlServidor
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.DatosServidorLocalRepository
import pe.ecolecta.domain.repository.EntregaServidor
import pe.ecolecta.domain.repository.JornadaServidor
import pe.ecolecta.domain.repository.ProveedorServidor
import pe.ecolecta.domain.repository.VehiculoServidor
import pe.ecolecta.domain.repository.ZonaServidor

/**
 * Aplica en la base local lo que publica el panel web. Reglas:
 * - Cada fila del servidor se reconoce por su id del panel (tabla `servidor_mapa`); la primera vez, por su
 *   nombre, placa, usuario o código, para no duplicar lo que el celular ya tenía.
 * - Solo se escribe lo que cambió: las pantallas no se refrescan en cada descarga si nada cambió.
 * - Lo que el celular aún no envió (entregas PENDING/ERROR, conflictos, jornadas propias) nunca se pisa.
 * - Con la copia completa del administrador, lo que ya no está en el panel se desactiva (no se borra).
 */
class SqlDelightDatosServidorRepository(
    private val db: EcolectaDatabase,
    private val reloj: Reloj,
    private val dispatcher: CoroutineDispatcher,
) : DatosServidorLocalRepository {

    override suspend fun aplicar(datos: DatosServidor) = withContext(dispatcher) {
        val ahora = reloj.ahora().toEpochMilliseconds()
        db.transaction {
            val zonas = datos.zonas.associate { it.id to zona(it) }
            val vehiculos = datos.vehiculos.associate { it.id to vehiculo(it) }
            val usuarios = datos.usuarios.mapNotNull { c -> cuenta(c, ahora)?.let { c.id to it } }.toMap()
            val proveedores = datos.proveedores.mapNotNull { p ->
                proveedor(p, zonas, usuarios, ahora)?.let { p.id to it }
            }.toMap()
            val jornadas = datos.jornadas.mapNotNull { j -> jornada(j, zonas, vehiculos, usuarios)?.let { j.id to it } }.toMap()
            datos.entregas.forEach { entrega(it, jornadas, proveedores, zonas, vehiculos, usuarios) }
            datos.controles.forEach { control(it, proveedores, usuarios) }
            comunicados(datos.comunicados)
            if (datos.completo) retirarAusentes(zonas.values.toSet(), vehiculos.values.toSet(), usuarios.values.toSet(), proveedores.values.toSet(), ahora)
        }
    }

    override suspend fun guardarCuenta(cuenta: CuentaServidor, pinHash: String, pinSalt: String, ahora: Long): String =
        withContext(dispatcher) {
            db.transactionWithResult {
                val id = cuenta(cuenta, ahora, forzar = true)!!
                db.usuarioQueries.actualizarPin(pin_hash = pinHash, pin_salt = pinSalt, updated_at = ahora, id = id)
                db.usuarioQueries.resetIntentos(updated_at = ahora, id = id)
                id
            }
        }

    private fun local(entidad: String, servidorId: Long): String? =
        db.servidorMapaQueries.localId(entidad, servidorId).executeAsOneOrNull()

    private fun mapear(entidad: String, servidorId: Long, localId: String) {
        if (local(entidad, servidorId) != localId) db.servidorMapaQueries.guardar(entidad, servidorId, localId)
    }

    private fun zona(z: ZonaServidor): String {
        val existente = local(ZONA, z.id)?.let { db.zonaQueries.selectPorId(it).executeAsOneOrNull() }
            ?: db.zonaQueries.selectPorNombre(z.nombre).executeAsOneOrNull()
        val activo = if (z.activo) 1L else 0L
        val id = existente?.id ?: "web-zona-${z.id}"
        when {
            existente == null -> db.zonaQueries.insertar(id = id, nombre = z.nombre, activo = activo)
            db.tieneCambioPendiente(EntidadCambio.ZONA, id) -> Unit
            existente.nombre != z.nombre || existente.activo != activo ->
                db.zonaQueries.actualizar(nombre = z.nombre, activo = activo, id = id)
        }
        mapear(ZONA, z.id, id)
        return id
    }

    private fun vehiculo(v: VehiculoServidor): String {
        val placa = v.placa.trim().uppercase()
        val existente = local(VEHICULO, v.id)?.let { db.vehiculoQueries.selectPorId(it).executeAsOneOrNull() }
            ?: db.vehiculoQueries.selectPorPlaca(placa).executeAsOneOrNull()
        val activo = if (v.activo) 1L else 0L
        val id = existente?.id ?: "web-vehiculo-${v.id}"
        when {
            existente == null -> db.vehiculoQueries.insertar(id = id, nombre = v.nombre, placa = placa, activo = activo)
            db.tieneCambioPendiente(EntidadCambio.VEHICULO, id) -> Unit
            existente.nombre != v.nombre || existente.placa != placa || existente.activo != activo ->
                db.vehiculoQueries.actualizar(nombre = v.nombre, placa = placa, activo = activo, id = id)
        }
        mapear(VEHICULO, v.id, id)
        return id
    }

    /**
     * Cuenta del panel. Una cuenta nueva se crea SIN PIN local (no se puede entrar sin conexión): la primera
     * vez entra validándose con el servidor y ahí se guarda su PIN ([guardarCuenta]). Una cuenta sin
     * ningún perfil de la app no se crea, y si ya existía queda inactiva en el celular.
     */
    private fun cuenta(c: CuentaServidor, ahora: Long, forzar: Boolean = false): String? {
        val roles = c.roles.mapNotNull { Rol.desde(it.uppercase()) }.distinct()
        val existente = local(USUARIO, c.id)?.let { db.usuarioQueries.selectPorId(it).executeAsOneOrNull() }
            ?: db.usuarioQueries.selectPorUsername(c.username).executeAsOneOrNull()
        val activo = if (c.activo && roles.isNotEmpty()) 1L else 0L
        val id: String
        if (existente == null) {
            if (roles.isEmpty() && !forzar) return null
            id = "web-usuario-${c.id}"
            db.usuarioQueries.insertar(
                id = id, username = c.username, nombres = c.nombres, dni = c.dni.orEmpty(),
                pin_hash = "", pin_salt = "", activo = activo, updated_at = ahora,
            )
        } else if (!forzar && db.tieneCambioPendiente(EntidadCambio.USUARIO, existente.id)) {
            // Cambio hecho en el celular aún sin enviar: se respeta hasta que llegue al panel.
            mapear(USUARIO, c.id, existente.id)
            return existente.id
        } else {
            id = existente.id
            val dni = c.dni?.takeIf { it.isNotBlank() } ?: existente.dni
            if (existente.nombres != c.nombres || existente.dni != dni || existente.activo != activo) {
                db.usuarioQueries.actualizar(nombres = c.nombres, dni = dni, activo = activo, updated_at = ahora, id = id)
            }
            if (existente.username != c.username && db.usuarioQueries.existeUsername(c.username, id).executeAsOne() == 0L) {
                db.usuarioQueries.cambiarUsername(username = c.username, updated_at = ahora, id = id)
            }
        }
        if (roles.isNotEmpty()) {
            val actuales = db.usuarioRolQueries.selectPorUsuario(id).executeAsList().toSet()
            if (actuales != roles.map { it.name }.toSet()) {
                db.usuarioRolQueries.eliminarTodosDe(id)
                roles.forEach { db.usuarioRolQueries.insertar(usuario_id = id, rol = it.name) }
            }
        }
        mapear(USUARIO, c.id, id)
        return id
    }

    private fun usuarioLocal(servidorId: Long, usuarios: Map<Long, String>): String =
        usuarios[servidorId] ?: local(USUARIO, servidorId) ?: "web-usuario-$servidorId"

    private fun proveedor(p: ProveedorServidor, zonas: Map<Long, String>, usuarios: Map<Long, String>, ahora: Long): String? {
        val zonaId = zonas[p.zonaId] ?: local(ZONA, p.zonaId) ?: return null
        val usuarioId = p.usuarioId?.let { usuarios[it] ?: local(USUARIO, it) }
        val estado = EstadoProveedor.entries.firstOrNull { it.name == p.estado.uppercase() } ?: EstadoProveedor.ACTIVO
        val existente = local(PROVEEDOR, p.id)?.let { db.proveedorQueries.selectPorId(it).executeAsOneOrNull() }
            ?: db.proveedorQueries.selectPorCodigo(p.codigo).executeAsOneOrNull()
        val id = existente?.id ?: "web-proveedor-${p.id}"
        if (existente != null && db.tieneCambioPendiente(EntidadCambio.PROVEEDOR, id)) {
            mapear(PROVEEDOR, p.id, id)
            return id
        }
        if (existente == null) {
            db.proveedorQueries.insertar(
                id = id, codigo = p.codigo, nombres = p.nombres, dni = p.dni, telefono = p.telefono, direccion = p.direccion,
                zona_id = zonaId, tachos = p.tachos.toLong(), capacidad_tacho_l = p.capacidadTachoL,
                estado = estado.name, updated_at = ahora, sync_state = "SYNCED",
            )
        } else {
            if (existente.nombres != p.nombres || existente.dni != p.dni || existente.telefono != p.telefono ||
                existente.direccion != p.direccion || existente.zona_id != zonaId || existente.tachos != p.tachos.toLong() ||
                existente.capacidad_tacho_l != p.capacidadTachoL
            ) {
                db.proveedorQueries.actualizar(
                    nombres = p.nombres, dni = p.dni, telefono = p.telefono, direccion = p.direccion, zona_id = zonaId,
                    tachos = p.tachos.toLong(), capacidad_tacho_l = p.capacidadTachoL, updated_at = ahora, id = id,
                )
            }
            if (existente.estado != estado.name) db.proveedorQueries.cambiarEstado(estado = estado.name, updated_at = ahora, id = id)
            if (existente.codigo != p.codigo && db.proveedorQueries.existeCodigo(p.codigo, id).executeAsOne() == 0L) {
                db.proveedorQueries.cambiarCodigo(codigo = p.codigo, id = id)
            }
        }
        if (usuarioId != null && existente?.usuario_id != usuarioId) {
            db.proveedorQueries.desvincularUsuario(usuarioId)
            db.proveedorQueries.vincularUsuario(usuario_id = usuarioId, id = id)
        }
        mapear(PROVEEDOR, p.id, id)
        return id
    }

    private fun jornada(
        j: JornadaServidor,
        zonas: Map<Long, String>,
        vehiculos: Map<Long, String>,
        usuarios: Map<Long, String>,
    ): String? {
        val id = j.uuidMovil ?: "web-jornada-${j.id}"
        val zonaId = zonas[j.zonaId] ?: return null
        val vehiculoId = vehiculos[j.vehiculoId] ?: return null
        val usuarioId = usuarioLocal(j.usuarioId, usuarios)
        db.jornadaQueries.insertarDesdeServidor(
            id = id, usuario_id = usuarioId, zona_id = zonaId, vehiculo_id = vehiculoId, fecha = j.fecha,
            abierta_en = j.abiertaEn, cerrada_en = j.cerradaEn,
        )
        val actual = db.jornadaQueries.selectPorId(id).executeAsOneOrNull()
        if (actual != null && actual.sync_state == "SYNCED" && !db.tieneCambioPendiente(EntidadCambio.JORNADA, id) &&
            (actual.usuario_id != usuarioId || actual.zona_id != zonaId || actual.vehiculo_id != vehiculoId ||
                actual.fecha != j.fecha || actual.abierta_en != j.abiertaEn || actual.cerrada_en != j.cerradaEn)
        ) {
            db.jornadaQueries.actualizarDesdeServidor(
                usuario_id = usuarioId, zona_id = zonaId, vehiculo_id = vehiculoId, fecha = j.fecha,
                abierta_en = j.abiertaEn, cerrada_en = j.cerradaEn, id = id,
            )
        }
        return id
    }

    private fun entrega(
        e: EntregaServidor,
        jornadas: Map<Long, String>,
        proveedores: Map<Long, String>,
        zonas: Map<Long, String>,
        vehiculos: Map<Long, String>,
        usuarios: Map<Long, String>,
    ) {
        val id = e.uuidMovil ?: "web-entrega-${e.id}"
        val jornadaId = jornadas[e.jornadaId] ?: return
        val proveedorId = proveedores[e.proveedorId] ?: local(PROVEEDOR, e.proveedorId) ?: return
        val zonaId = zonas[e.zonaId] ?: return
        val vehiculoId = vehiculos[e.vehiculoId] ?: return
        val anulada = if (e.anulada) 1L else 0L
        db.entregaQueries.insertarDesdeServidor(
            id = id, jornada_id = jornadaId, proveedor_id = proveedorId, usuario_id = usuarioLocal(e.usuarioId, usuarios),
            zona_id = zonaId, vehiculo_id = vehiculoId, litros = e.litros, tachos = e.tachos.toLong(),
            observaciones = e.observaciones, registrado_en = e.registradoEn, anulada = anulada,
            updated_at = e.actualizadoEn.takeIf { it > 0 } ?: e.registradoEn,
        )
        db.entregaQueries.actualizarDesdeServidor(
            litros = e.litros, tachos = e.tachos.toLong(), observaciones = e.observaciones, anulada = anulada, id = id,
        )
    }

    private fun control(c: ControlServidor, proveedores: Map<Long, String>, usuarios: Map<Long, String>) {
        val proveedorId = proveedores[c.proveedorId] ?: local(PROVEEDOR, c.proveedorId) ?: return
        // Análisis hecho en un celular: ese celular ya tiene su copia original (con todas las lecturas).
        if (c.uuidMovil != null && db.controlCalidadQueries.selectPorId(c.uuidMovil).executeAsOneOrNull() != null) return
        val estado = EstadoControlCalidad.entries.firstOrNull { it.name == c.resultado.uppercase() } ?: return
        val id = "web-control-${c.id}"
        val observaciones = listOfNotNull(c.observaciones?.takeIf { it.isNotBlank() }, c.acidez?.let { "Acidez: $it °D" })
            .joinToString(" · ").ifBlank { null }
        val actual = db.controlCalidadQueries.selectPorId(id).executeAsOneOrNull()
        if (actual != null && actual.estado == estado.name && actual.temperatura == c.temperatura &&
            actual.observaciones == observaciones && actual.registrado_en == c.evaluadoEn && actual.proveedor_id == proveedorId
        ) return
        db.controlCalidadQueries.guardarDesdeServidor(
            id = id, proveedor_id = proveedorId, usuario_id = usuarioLocal(c.usuarioId, usuarios), codigo_muestra = "WEB-${c.id}",
            temperatura = c.temperatura, observaciones = observaciones, estado = estado.name, alertas = "",
            registrado_en = c.evaluadoEn,
        )
    }

    /** Los comunicados del panel reemplazan a su copia; los que se despublicaron allí desaparecen del celular. */
    private fun comunicados(lista: List<ComunicadoServidor>) {
        val actuales = db.comunicadoQueries.selectTodos().executeAsList()
            .filter { it.id.startsWith(PREFIJO_COMUNICADO) }.associateBy { it.id }
        val vigentes = mutableSetOf<String>()
        val locales = db.comunicadoQueries.selectTodos().executeAsList().map { it.id }.toSet()
        lista.forEach { c ->
            // Publicado desde un celular (código MOV-<id local>): si es este, ya tiene su copia.
            if (c.codigo.startsWith("MOV-") && c.codigo.removePrefix("MOV-") in locales) return@forEach
            val id = "$PREFIJO_COMUNICADO${c.id}"
            vigentes += id
            val mensaje = "${c.titulo}\n${c.contenido}"
            val actual = actuales[id]
            if (actual == null || actual.mensaje != mensaje || actual.autor_nombre != c.autor || actual.publicado_en != c.publicadoEn) {
                db.comunicadoQueries.guardar(id, mensaje, "panel-web", c.autor, c.publicadoEn)
            }
        }
        (actuales.keys - vigentes).forEach { db.comunicadoQueries.eliminar(it) }
    }

    private fun retirarAusentes(zonas: Set<String>, vehiculos: Set<String>, usuarios: Set<String>, proveedores: Set<String>, ahora: Long) {
        db.zonaQueries.selectActivas().executeAsList().filter { it.id !in zonas && !db.tieneCambioPendiente(EntidadCambio.ZONA, it.id) }.forEach { db.zonaQueries.desactivar(it.id) }
        db.vehiculoQueries.selectActivos().executeAsList().filter { it.id !in vehiculos && !db.tieneCambioPendiente(EntidadCambio.VEHICULO, it.id) }.forEach { db.vehiculoQueries.desactivar(it.id) }
        db.proveedorQueries.selectTodos().executeAsList()
            .filter { it.id !in proveedores && it.estado != EstadoProveedor.RETIRADO.name && !db.tieneCambioPendiente(EntidadCambio.PROVEEDOR, it.id) }
            .forEach { db.proveedorQueries.cambiarEstado(estado = EstadoProveedor.RETIRADO.name, updated_at = ahora, id = it.id) }
        // Cuentas: solo las que vinieron alguna vez del panel. Una cuenta creada solo en este celular no se toca.
        db.servidorMapaQueries.localesDe(USUARIO).executeAsList().filter { it !in usuarios }.forEach { id ->
            if (db.usuarioQueries.selectPorId(id).executeAsOneOrNull()?.activo == 1L) db.usuarioQueries.desactivar(ahora, id)
        }
    }

    private companion object {
        const val ZONA = "zona"
        const val VEHICULO = "vehiculo"
        const val USUARIO = "usuario"
        const val PROVEEDOR = "proveedor"
        const val PREFIJO_COMUNICADO = "web-comunicado-"
    }
}
