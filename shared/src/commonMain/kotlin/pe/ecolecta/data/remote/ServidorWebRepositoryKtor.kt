package pe.ecolecta.data.remote

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToOneOrNull
import io.ktor.client.HttpClient
import io.ktor.client.call.body
import io.ktor.client.request.bearerAuth
import io.ktor.client.request.delete
import io.ktor.client.request.get
import io.ktor.client.request.post
import io.ktor.client.request.put
import io.ktor.client.request.setBody
import io.ktor.client.statement.HttpResponse
import io.ktor.http.ContentType
import io.ktor.http.HttpStatusCode
import io.ktor.http.contentType
import io.ktor.http.isSuccess
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.TimeoutCancellationException
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeout
import kotlinx.serialization.SerialName
import kotlinx.serialization.Serializable
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.PREFIJO_PAGO_SERVIDOR
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SesionServidor
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinSesionServidorException
import pe.ecolecta.domain.repository.UsuarioRepository

@Serializable private data class SolicitudSesion(val username: String, val pin: String, val dispositivo: String)
@Serializable private data class RespuestaSesion(
    val token: String,
    val expiraEn: Long,
    val usuario: CuentaServidor? = null,
    @SerialName("refresh_token") val refreshToken: String? = null,
    val refreshExpiraEn: Long? = null,
)
@Serializable private data class SolicitudRefresh(@SerialName("refresh_token") val refreshToken: String)
@Serializable private data class RespuestaError(val message: String? = null, val codigo: String? = null, val definitivo: Boolean = true)
@Serializable private data class RespuestaCambio(val id: Long? = null, val revision: String? = null)
@Serializable private data class LiquidacionServidor(
    val id: Long,
    val desde: String,
    val hasta: String,
    val litros: Double,
    val precio: Double,
    val bruto: Double,
    val descuento: Double,
    val total: Double,
    val estado: String,
    val fechaPago: String? = null,
    val porTemporadas: Boolean = false,
)
@Serializable private data class RespuestaLiquidaciones(val proveedorCodigo: String, val liquidaciones: List<LiquidacionServidor>)

/**
 * API móvil del panel web (`/api/movil/...`, ver web/routes/api.php). Un envío solo es éxito cuando el
 * servidor respondió 2xx; si no hubo respuesta (sin red, servidor apagado, tiempo agotado) se informa
 * [SinConexionRemotaException] para reintentar sin marcar error. El token se guarda por usuario local.
 */
class ServidorWebRepositoryKtor(
    urlBase: String,
    private val http: HttpClient,
    private val db: EcolectaDatabase,
    private val usuarios: UsuarioRepository,
    private val reloj: Reloj,
    private val io: CoroutineDispatcher,
    private val nombreDispositivo: String = "App Ecolecta",
    private val protector: pe.ecolecta.domain.security.ProtectorSecretos = pe.ecolecta.domain.security.ProtectorSecretos.NoDisponible,
) : ServidorWebRepository {
    override val direccion: String = urlBase.trimEnd('/')
    private val api = "$direccion/api/movil"
    private val sesionMutex = Mutex()

    override val configurado: Boolean = true

    override suspend fun vincular(usuarioId: String, username: String, pin: String): Result<Unit> {
        val sesion = autenticar(username, pin).getOrElse { return Result.failure(it) }
        return try {
            guardarSesion(usuarioId, sesion)
            Result.success(Unit)
        } catch (cancelacion: CancellationException) {
            throw cancelacion
        } catch (error: Exception) {
            Result.failure(error)
        }
    }

    override suspend fun autenticar(username: String, pin: String): Result<SesionServidor> = llamar {
        val respuesta = http.post("$api/sesion") {
            contentType(ContentType.Application.Json)
            setBody(SolicitudSesion(username, pin, nombreDispositivo))
        }
        if (!respuesta.status.isSuccess()) throw rechazo(respuesta)
        val sesion = respuesta.body<RespuestaSesion>()
        SesionServidor(sesion.token, sesion.expiraEn, sesion.usuario, sesion.refreshToken, sesion.refreshExpiraEn)
    }

    override suspend fun guardarSesion(usuarioId: String, sesion: SesionServidor) {
        sesionMutex.withLock { persistirSesion(usuarioId, sesion) }
    }

    private suspend fun persistirSesion(usuarioId: String, sesion: SesionServidor) {
        withContext(io) {
            db.tokenServidorQueries.guardarConRefresh(
                usuarioId, protector.proteger(sesion.token, "access:$usuarioId"), sesion.expiraEn, reloj.ahora().toEpochMilliseconds(),
                sesion.refreshToken?.let { protector.proteger(it, "refresh:$usuarioId") }, sesion.refreshExpiraEn,
            )
        }
    }

    override suspend fun enviarCambio(usuarioId: String, entidad: String, cuerpo: kotlinx.serialization.json.JsonObject): Result<Long?> =
        enviarCatalogo(usuarioId, entidad, cuerpo).map { it.servidorId }

    override suspend fun enviarCatalogo(usuarioId: String, entidad: String, cuerpo: kotlinx.serialization.json.JsonObject): Result<pe.ecolecta.domain.repository.ConfirmacionCatalogo> = llamar {
        val respuesta = peticionAutenticada(usuarioId) { token ->
            http.put("$api/cambios/$entidad") {
                bearerAuth(token)
                contentType(ContentType.Application.Json)
                setBody(cuerpo)
            }
        }
        val ack = respuesta.body<RespuestaCambio>()
        pe.ecolecta.domain.repository.ConfirmacionCatalogo(ack.id, ack.revision)
    }

    override suspend fun descargarDatos(usuarioId: String): Result<DatosServidor> = llamar {
        val respuesta = peticionAutenticada(usuarioId) { token -> http.get("$api/datos") { bearerAuth(token) } }
        respuesta.body<DatosServidor>()
    }

    override fun observarSesion(usuarioId: String): Flow<Boolean> =
        db.tokenServidorQueries.porUsuario(usuarioId).asFlow().mapToOneOrNull(io)
            .map { fila ->
                val ahora = reloj.ahora().toEpochMilliseconds()
                fila != null && (fila.expira_en > ahora || (fila.refresh_token != null && (fila.refresh_expira_en ?: 0) > ahora))
            }

    override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor): Result<Unit> = llamar<Unit> {
        peticionAutenticada(usuarioId) { token ->
            http.put("$api/entregas/${entrega.id}") {
                bearerAuth(token)
                contentType(ContentType.Application.Json)
                setBody(entrega)
            }
        }
    }

    override suspend fun liquidacionesDelProveedor(usuarioId: String): Result<List<PagoProveedor>> = llamar {
        val respuesta = peticionAutenticada(usuarioId) { token -> http.get("$api/proveedor/liquidaciones") { bearerAuth(token) } }
        respuesta.body<RespuestaLiquidaciones>().liquidaciones.map {
            PagoProveedor(
                id = "$PREFIJO_PAGO_SERVIDOR${it.id}", proveedorId = "", desde = it.desde, hasta = it.hasta,
                litros = it.litros, precio = it.precio, bruto = it.bruto, descuento = it.descuento, total = it.total,
                estado = it.estado, fechaPago = it.fechaPago, porTemporadas = it.porTemporadas,
            )
        }
    }

    override suspend fun desvincular(usuarioId: String) {
        sesionMutex.withLock {
            val fila = withContext(io) { db.tokenServidorQueries.porUsuario(usuarioId).executeAsOneOrNull() } ?: return
            llamar {
                http.delete("$api/sesion") {
                    bearerAuth(protector.abrir(fila.token, "access:$usuarioId"))
                    if (fila.refresh_token != null) {
                        contentType(ContentType.Application.Json)
                        setBody(SolicitudRefresh(protector.abrir(fila.refresh_token, "refresh:$usuarioId")))
                    }
                }
            }
            withContext(io) { db.tokenServidorQueries.borrar(usuarioId) }
        }
    }

    private suspend fun tokenVigente(usuarioId: String): String {
        val fila = withContext(io) { db.tokenServidorQueries.porUsuario(usuarioId).executeAsOneOrNull() }
        if (fila == null) {
            throw SinSesionServidorException(usuarios.obtenerPorId(usuarioId)?.nombres)
        }
        if (fila.expira_en <= reloj.ahora().toEpochMilliseconds()) {
            return renovarToken(usuarioId)
        }
        return protector.abrir(fila.token, "access:$usuarioId")
    }

    /** Serializa rotaciones para que dos envíos del mismo celular no reutilicen el refresh token. */
    private suspend fun peticionAutenticada(usuarioId: String, enviar: suspend (String) -> HttpResponse): HttpResponse =
        sesionMutex.withLock {
            var respuesta = enviar(tokenVigente(usuarioId))
            if (respuesta.status == HttpStatusCode.Unauthorized) {
                respuesta = enviar(renovarToken(usuarioId))
            }
            verificar(usuarioId, respuesta)
            respuesta
        }

    private suspend fun renovarToken(usuarioId: String): String {
        val fila = withContext(io) { db.tokenServidorQueries.porUsuario(usuarioId).executeAsOneOrNull() }
        if (fila?.refresh_token == null || (fila.refresh_expira_en ?: 0) <= reloj.ahora().toEpochMilliseconds()) {
            invalidarSesionLocal(usuarioId)
            throw SinSesionServidorException(usuarios.obtenerPorId(usuarioId)?.nombres, revocada = true)
        }
        val respuesta = http.post("$api/sesion/refresh") {
            contentType(ContentType.Application.Json)
            setBody(SolicitudRefresh(protector.abrir(fila.refresh_token, "refresh:$usuarioId")))
        }
        verificar(usuarioId, respuesta)
        val sesion = respuesta.body<RespuestaSesion>()
        persistirSesion(usuarioId, SesionServidor(sesion.token, sesion.expiraEn, sesion.usuario, sesion.refreshToken, sesion.refreshExpiraEn))
        return sesion.token
    }

    /** 401 = el servidor ya no reconoce el token (vencido, revocado o cuenta desactivada): se borra. */
    private suspend fun verificar(usuarioId: String, respuesta: HttpResponse) {
        if (respuesta.status.isSuccess()) return
        val error = rechazo(respuesta)
        if (respuesta.status == HttpStatusCode.Unauthorized ||
            (respuesta.status == HttpStatusCode.Forbidden && error.codigo in setOf("cuenta_bloqueada", "cuenta_suspendida", "perfil_no_disponible", "token_invalido"))
        ) {
            invalidarSesionLocal(usuarioId)
            throw SinSesionServidorException(usuarios.obtenerPorId(usuarioId)?.nombres, revocada = true)
        }
        throw error
    }

    private suspend fun invalidarSesionLocal(usuarioId: String) = withContext(io) {
        db.transaction {
            db.tokenServidorQueries.borrar(usuarioId)
            db.usuarioQueries.requerirVerificacion(usuarioId)
            db.sesionQueries.limpiarDeUsuario(usuarioId)
        }
    }

    private suspend fun rechazo(respuesta: HttpResponse): RechazoServidorException {
        val error = runCatching { respuesta.body<RespuestaError>() }.getOrNull()
        return RechazoServidorException(
            error?.message ?: "El servidor respondió ${respuesta.status.value}.",
            error?.codigo,
            // Los errores del servidor y los límites de peticiones se reintentan.
            definitivo = (error?.definitivo ?: true) && respuesta.status.value < 500 && respuesta.status.value != 429,
        )
    }

    /**
     * Toda excepción que no sea una respuesta del servidor (sin red, DNS, conexión rechazada, tiempo
     * agotado) se trata como falta de conexión: el dato se conserva y se reintenta.
     */
    private suspend fun <T> llamar(bloque: suspend () -> T): Result<T> = try {
        Result.success(withTimeout(ESPERA_MS) { bloque() })
    } catch (e: TimeoutCancellationException) {
        Result.failure(SinConexionRemotaException(e, "$SIN_RESPUESTA en $direccion"))
    } catch (e: CancellationException) {
        throw e
    } catch (e: RechazoServidorException) {
        Result.failure(e)
    } catch (e: SinSesionServidorException) {
        Result.failure(e)
    } catch (e: pe.ecolecta.domain.security.SecretoLocalNoDisponibleException) {
        Result.failure(e)
    } catch (e: kotlinx.serialization.SerializationException) {
        Result.failure(RechazoServidorException("El panel respondió con datos incompatibles. Actualiza el panel web o la app; los cambios locales se conservan.", "contrato_incompatible", false))
    } catch (e: io.ktor.serialization.ContentConvertException) {
        Result.failure(RechazoServidorException("No se pudo interpretar la respuesta del panel web. Los cambios locales se conservan.", "contrato_incompatible", false))
    } catch (e: Exception) {
        Result.failure(SinConexionRemotaException(e, "$SIN_RESPUESTA en $direccion"))
    }

    private companion object {
        const val ESPERA_MS = 15_000L
        const val SIN_RESPUESTA = "El panel web no respondió (sin internet o servidor no disponible)"

    }
}

/** Compilación sin URL de servidor (iOS, o Android sin `ecolecta.servidorUrl`): nunca finge un envío. */
class ServidorWebNoConfigurado : ServidorWebRepository {
    override val configurado: Boolean = false
    private fun noDisponible() = Result.failure<Nothing>(UnsupportedOperationException("Este celular no tiene servidor configurado."))
    override suspend fun vincular(usuarioId: String, username: String, pin: String): Result<Unit> = noDisponible()
    override fun observarSesion(usuarioId: String): Flow<Boolean> = kotlinx.coroutines.flow.flowOf(false)
    override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor): Result<Unit> = noDisponible()
    override suspend fun liquidacionesDelProveedor(usuarioId: String): Result<List<PagoProveedor>> = noDisponible()
    override suspend fun desvincular(usuarioId: String) = Unit
}
