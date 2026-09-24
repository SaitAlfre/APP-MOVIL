package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import app.cash.sqldelight.coroutines.mapToOne
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.withContext
import kotlinx.serialization.json.Json
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.JsonObjectBuilder
import kotlinx.serialization.json.JsonPrimitive
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put
import kotlinx.serialization.json.putJsonArray
import pe.ecolecta.data.local.EntidadCambio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.local.marcarCambio
import pe.ecolecta.db.Cambio_pendiente
import pe.ecolecta.domain.model.PREFIJO_PAGO_SERVIDOR
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.domain.repository.CambioParaServidor
import pe.ecolecta.domain.repository.CambiosLocalesRepository
import pe.ecolecta.domain.repository.EstadoCambiosServidor
import pe.ecolecta.domain.repository.PinesParaServidor

/**
 * Cola celular -> panel. Arma el cuerpo de cada envío con el estado ACTUAL de la fila (ver
 * AplicarCambioMovilUseCase en el panel) y, al confirmarse, recuerda el id del panel en `servidor_mapa`.
 * Las filas que nacieron en el panel (`web-...`) viajan con ese id para no duplicarse allí.
 */
class SqlDelightCambiosLocalesRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : CambiosLocalesRepository, PinesParaServidor {
    private val json = Json { ignoreUnknownKeys = true }

    override suspend fun recordarPin(usuarioId: String, pin: String) = withContext(dispatcher) {
        db.marcarCambio(EntidadCambio.USUARIO, usuarioId, pin)
    }

    override suspend fun pendientes(): List<CambioParaServidor> = withContext(dispatcher) {
        db.cambioPendienteQueries.pendientes().executeAsList().map { fila ->
            CambioParaServidor(fila.entidad, fila.local_id, fila.usuario_id, fila.marcado_en, cuerpo(fila))
        }
    }

    override suspend fun completar(cambio: CambioParaServidor, servidorId: Long?) = withContext(dispatcher) {
        db.transaction {
            if (servidorId != null && cambio.entidad in ENTIDADES_MAPEADAS) {
                db.servidorMapaQueries.guardar(cambio.entidad, servidorId, cambio.localId)
            }
            db.cambioPendienteQueries.completar(cambio.entidad, cambio.localId, cambio.marcadoEn)
        }
    }

    override suspend fun fallar(cambio: CambioParaServidor, mensaje: String, definitivo: Boolean) = withContext(dispatcher) {
        db.cambioPendienteQueries.registrarFallo(if (definitivo) "ERROR" else "PENDING", mensaje, cambio.entidad, cambio.localId, cambio.marcadoEn)
        Unit
    }

    override fun observarEstado(): Flow<EstadoCambiosServidor> = combine(
        db.cambioPendienteQueries.contarPorEstado("PENDING").asFlow().mapToOne(dispatcher),
        db.cambioPendienteQueries.errores().asFlow().mapToList(dispatcher),
    ) { pendientes, errores ->
        EstadoCambiosServidor(pendientes, errores.size.toLong(), errores.firstOrNull()?.ultimo_error)
    }

    private fun servidorId(entidad: String, localId: String): Long? =
        db.servidorMapaQueries.servidorId(entidad, localId).executeAsOneOrNull()
            ?: localId.removePrefix("web-$entidad-").takeIf { localId.startsWith("web-$entidad-") }?.toLongOrNull()

    private fun JsonObjectBuilder.putServidor(entidad: String, localId: String, clave: String = "servidorId") {
        servidorId(entidad, localId)?.let { put(clave, it) }
    }

    /** null = ya no hay nada que enviar (la fila se borró o no es de un tipo que el panel reciba). */
    private fun cuerpo(c: Cambio_pendiente): JsonObject? = when (c.entidad) {
        EntidadCambio.ZONA -> db.zonaQueries.selectPorId(c.local_id).executeAsOneOrNull()?.let { z ->
            buildJsonObject { putServidor(c.entidad, z.id); put("nombre", z.nombre); put("activo", z.activo == 1L) }
        }
        EntidadCambio.VEHICULO -> db.vehiculoQueries.selectPorId(c.local_id).executeAsOneOrNull()?.let { v ->
            buildJsonObject { putServidor(c.entidad, v.id); put("nombre", v.nombre); put("placa", v.placa); put("activo", v.activo == 1L) }
        }
        EntidadCambio.USUARIO -> db.usuarioQueries.selectPorId(c.local_id).executeAsOneOrNull()?.let { u ->
            val roles = db.usuarioRolQueries.selectPorUsuario(u.id).executeAsList()
            if (roles.isEmpty()) return null
            buildJsonObject {
                putServidor(c.entidad, u.id)
                put("username", u.username); put("nombres", u.nombres); put("dni", u.dni); put("activo", u.activo == 1L)
                putJsonArray("roles") { roles.forEach { add(JsonPrimitive(it)) } }
                c.pin?.let { put("pin", it) }
            }
        }
        EntidadCambio.PROVEEDOR -> db.proveedorQueries.selectPorId(c.local_id).executeAsOneOrNull()?.let { p ->
            val zona = db.zonaQueries.selectPorId(p.zona_id).executeAsOneOrNull() ?: return null
            val cuenta = p.usuario_id?.let { db.usuarioQueries.selectPorId(it).executeAsOneOrNull() }
            buildJsonObject {
                putServidor(c.entidad, p.id)
                put("codigo", p.codigo); put("nombres", p.nombres); put("dni", p.dni)
                put("telefono", p.telefono); put("direccion", p.direccion)
                putServidor(EntidadCambio.ZONA, zona.id, "zonaId"); put("zonaNombre", zona.nombre)
                put("tachos", p.tachos); put("capacidadTachoL", p.capacidad_tacho_l); put("estado", p.estado)
                put("usuarioUsername", cuenta?.username)
            }
        }
        EntidadCambio.JORNADA -> db.jornadaQueries.selectPorId(c.local_id).executeAsOneOrNull()?.let { j ->
            val zona = db.zonaQueries.selectPorId(j.zona_id).executeAsOneOrNull() ?: return null
            val vehiculo = db.vehiculoQueries.selectPorId(j.vehiculo_id).executeAsOneOrNull() ?: return null
            buildJsonObject {
                put("uuid", j.id)
                putServidor(EntidadCambio.ZONA, zona.id, "zonaId"); put("zonaNombre", zona.nombre)
                putServidor(EntidadCambio.VEHICULO, vehiculo.id, "vehiculoId"); put("vehiculoPlaca", vehiculo.placa)
                put("abiertaEn", j.abierta_en); put("cerradaEn", j.cerrada_en)
            }
        }
        EntidadCambio.COMUNICADO -> {
            val fila = db.comunicadoQueries.selectTodos().executeAsList().firstOrNull { it.id == c.local_id }
            buildJsonObject {
                put("uuid", c.local_id)
                putServidor(c.entidad, c.local_id)
                put("mensaje", fila?.mensaje); put("publicadoEn", fila?.publicado_en ?: 0L); put("eliminado", fila == null)
            }
        }
        EntidadCambio.CALIDAD -> db.controlCalidadQueries.selectPorId(c.local_id).executeAsOneOrNull()
            ?.takeIf { !it.id.startsWith("web-") }?.let { cc ->
                val proveedor = db.proveedorQueries.selectPorId(cc.proveedor_id).executeAsOneOrNull() ?: return null
                buildJsonObject {
                    put("uuid", cc.id)
                    putServidor(EntidadCambio.PROVEEDOR, proveedor.id, "proveedorId"); put("proveedorCodigo", proveedor.codigo)
                    put("codigoMuestra", cc.codigo_muestra); put("loteRecipiente", cc.lote_recipiente); put("volumenL", cc.volumen_l)
                    put("origenCaptura", cc.origen_captura); put("serialAnalizador", cc.serial_analizador); put("modoAnalizador", cc.modo_analizador)
                    put("temperatura", cc.temperatura); put("grasa", cc.grasa); put("sng", cc.sng); put("densidad", cc.densidad)
                    put("proteina", cc.proteina); put("lactosa", cc.lactosa); put("sales", cc.sales); put("solidosTotales", cc.solidos_totales)
                    put("aguaAnadida", cc.agua_anadida); put("puntoCongelacion", cc.punto_congelacion); put("ph", cc.ph)
                    put("apariencia", cc.apariencia); put("observaciones", cc.observaciones); put("estado", cc.estado)
                    putJsonArray("alertas") { cc.alertas.lines().filter { it.isNotBlank() }.forEach { add(JsonPrimitive(it)) } }
                    put("textoComprobante", cc.texto_comprobante)
                    put("visita", runCatching { json.parseToJsonElement(cc.visita_json) }.getOrNull() as? JsonObject ?: JsonObject(emptyMap()))
                    put("registradoEn", cc.registrado_en)
                }
            }
        EntidadCambio.SOLICITUD -> contenido<SolicitudProveedor>(c.local_id)?.takeIf { it.tipo == "RECLAMO" }?.let { s ->
            buildJsonObject {
                put("uuid", s.id); put("entregaUuid", s.referenciaId); put("litros", s.litros)
                put("motivo", listOf(s.motivo, s.descripcion).filter { it.isNotBlank() }.distinct().joinToString(": "))
                put("estado", s.estado)
            }
        }
        EntidadCambio.LIQUIDACION -> contenido<PagoProveedor>(c.local_id)
            ?.takeIf { !it.id.startsWith(PREFIJO_PAGO_SERVIDOR) && it.estado.uppercase() in setOf("APROBADA", "PAGADA") }?.let { pago ->
                val proveedor = db.proveedorQueries.selectPorId(pago.proveedorId).executeAsOneOrNull() ?: return null
                buildJsonObject {
                    putServidor(EntidadCambio.PROVEEDOR, proveedor.id, "proveedorId"); put("proveedorCodigo", proveedor.codigo)
                    put("desde", pago.desde); put("hasta", pago.hasta); put("precio", pago.precio); put("estado", pago.estado.uppercase())
                }
            }
        else -> null
    }

    private inline fun <reified T> contenido(id: String): T? =
        db.portalProveedorQueries.porId(id).executeAsOneOrNull()?.let { runCatching { json.decodeFromString<T>(it.contenido) }.getOrNull() }

    private companion object {
        val ENTIDADES_MAPEADAS = setOf(EntidadCambio.ZONA, EntidadCambio.VEHICULO, EntidadCambio.USUARIO, EntidadCambio.PROVEEDOR)
    }
}
