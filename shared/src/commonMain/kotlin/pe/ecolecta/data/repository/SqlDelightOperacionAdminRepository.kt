package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Comunicado
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.domain.repository.AlertaDescartadaRepository
import pe.ecolecta.domain.repository.ComunicadoRepository
import pe.ecolecta.domain.repository.GestionPortalRepository

class SqlDelightGestionPortalRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : GestionPortalRepository {
    private val json = Json { ignoreUnknownKeys = true }

    override fun todasSolicitudes(): Flow<List<SolicitudProveedor>> =
        db.portalProveedorQueries.porTipo("SOLICITUD").asFlow().mapToList(dispatcher)
            .map { filas -> filas.mapNotNull { runCatching { json.decodeFromString<SolicitudProveedor>(it.contenido) }.getOrNull() } }

    override fun todosPagos(): Flow<List<PagoProveedor>> =
        db.portalProveedorQueries.porTipo("PAGO").asFlow().mapToList(dispatcher)
            .map { filas -> filas.mapNotNull { runCatching { json.decodeFromString<PagoProveedor>(it.contenido) }.getOrNull() } }

    override suspend fun actualizarSolicitud(solicitud: SolicitudProveedor) = withContext(dispatcher) {
        db.portalProveedorQueries.actualizarContenido(json.encodeToString(solicitud), solicitud.id)
        Unit
    }

    override suspend fun guardarPagos(pagos: List<PagoProveedor>, en: Long) = withContext(dispatcher) {
        db.transaction {
            pagos.forEach { pago ->
                val contenido = json.encodeToString(pago)
                val existe = db.portalProveedorQueries.porProveedor(pago.proveedorId).executeAsList().any { it.id == pago.id }
                if (existe) {
                    db.portalProveedorQueries.actualizarContenido(contenido, pago.id)
                } else {
                    db.portalProveedorQueries.guardar(pago.id, pago.proveedorId, "PAGO", contenido, en)
                }
            }
        }
    }
}

class SqlDelightComunicadoRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : ComunicadoRepository {
    override fun observarTodos(): Flow<List<Comunicado>> =
        db.comunicadoQueries.selectTodos().asFlow().mapToList(dispatcher).map { filas ->
            filas.map { Comunicado(it.id, it.mensaje, it.autor_id, it.autor_nombre, it.publicado_en) }
        }

    override suspend fun publicar(comunicado: Comunicado) = withContext(dispatcher) {
        db.comunicadoQueries.insertar(comunicado.id, comunicado.mensaje, comunicado.autorId, comunicado.autorNombre, comunicado.publicadoEn)
        Unit
    }

    override suspend fun eliminar(id: String) = withContext(dispatcher) {
        db.comunicadoQueries.eliminar(id)
        Unit
    }
}

class SqlDelightAlertaDescartadaRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : AlertaDescartadaRepository {
    override fun observarIds(): Flow<Set<String>> =
        db.alertaDescartadaQueries.selectIds().asFlow().mapToList(dispatcher).map { it.toSet() }

    override suspend fun descartar(id: String, usuarioId: String, en: Long) = withContext(dispatcher) {
        db.alertaDescartadaQueries.insertar(id, usuarioId, en)
        Unit
    }
}

class SqlDelightCuentasRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : pe.ecolecta.domain.repository.CuentasRepository {
    override fun observarZonasAsignadas(): Flow<Map<String, String>> =
        db.usuarioZonaQueries.selectTodas().asFlow().mapToList(dispatcher).map { filas -> filas.associate { it.usuario_id to it.zona_id } }

    override suspend fun zonaAsignada(usuarioId: String): String? = withContext(dispatcher) {
        db.usuarioZonaQueries.selectZonaId(usuarioId).executeAsOneOrNull()
    }

    override suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?) = withContext(dispatcher) {
        db.transaction {
            if (zonaId == null) db.usuarioZonaQueries.quitar(usuarioId) else db.usuarioZonaQueries.asignar(usuarioId, zonaId)
            db.proveedorQueries.desvincularUsuario(usuarioId)
            if (proveedorId != null) db.proveedorQueries.vincularUsuario(usuario_id = usuarioId, id = proveedorId)
        }
    }

    override suspend fun crearCuenta(
        usuario: pe.ecolecta.domain.model.Usuario,
        zonaId: String?,
        fichaNueva: pe.ecolecta.domain.model.Proveedor?,
        fichaExistenteId: String?,
        auditorias: List<pe.ecolecta.domain.model.Auditoria>,
    ) = withContext(dispatcher) {
        db.transaction {
            db.usuarioQueries.insertar(
                id = usuario.id, username = usuario.username, nombres = usuario.nombres, dni = usuario.dni,
                pin_hash = usuario.pinHash, pin_salt = usuario.pinSalt, activo = if (usuario.activo) 1 else 0, updated_at = usuario.updatedAt,
            )
            usuario.roles.forEach { rol -> db.usuarioRolQueries.insertar(usuario_id = usuario.id, rol = rol.name) }
            if (zonaId != null) db.usuarioZonaQueries.asignar(usuario.id, zonaId)
            if (fichaNueva != null) {
                db.proveedorQueries.insertar(
                    id = fichaNueva.id, codigo = fichaNueva.codigo, nombres = fichaNueva.nombres, dni = fichaNueva.dni,
                    telefono = fichaNueva.telefono, direccion = fichaNueva.direccion, zona_id = fichaNueva.zonaId,
                    tachos = fichaNueva.tachos.toLong(), capacidad_tacho_l = fichaNueva.capacidadTachoL,
                    estado = fichaNueva.estado.name, updated_at = fichaNueva.updatedAt, sync_state = fichaNueva.syncState.name,
                )
                db.proveedorQueries.actualizarResponsable(fichaNueva.dueno, fichaNueva.id)
                db.proveedorQueries.vincularUsuario(usuario_id = usuario.id, id = fichaNueva.id)
            }
            if (fichaExistenteId != null) {
                // Se vuelve a comprobar dentro de la transacción: otra alta pudo tomar la ficha entretanto.
                val ficha = db.proveedorQueries.selectPorId(fichaExistenteId).executeAsOneOrNull()
                    ?: throw IllegalArgumentException("La ficha de proveedor ya no existe.")
                if (ficha.usuario_id != null) throw IllegalArgumentException("La ficha ${ficha.codigo} ya está vinculada a otra cuenta.")
                db.proveedorQueries.vincularUsuario(usuario_id = usuario.id, id = fichaExistenteId)
            }
            auditorias.forEach { a ->
                db.auditoriaQueries.insertar(
                    id = a.id, entidad = a.entidad, entidad_id = a.entidadId, accion = a.accion.name,
                    valor_antes = a.valorAntes, valor_despues = a.valorDespues, motivo = a.motivo, usuario_id = a.usuarioId,
                    ocurrido_en = a.ocurridoEn, device_id = a.deviceId, sync_state = a.syncState.name,
                )
            }
        }
    }

    override suspend fun existeNombreZona(nombre: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.zonaQueries.existeNombre(nombre, idExcluido).executeAsOne() > 0
    }

    override suspend fun vehiculoEnJornadaAbierta(vehiculoId: String): Boolean = withContext(dispatcher) {
        db.jornadaQueries.selectAbiertaPorVehiculo(vehiculoId).executeAsOneOrNull() != null
    }
}
