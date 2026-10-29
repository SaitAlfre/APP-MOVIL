package pe.ecolecta.data

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking
import kotlinx.serialization.json.JsonObject
import org.junit.Assume.assumeTrue
import org.koin.dsl.koinApplication
import org.koin.dsl.module
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente
import pe.ecolecta.di.dataModule
import pe.ecolecta.di.domainModule
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.admin.fechaLima
import pe.ecolecta.domain.usecase.sync.EnviarCambiosServidorUseCase
import java.net.CookieManager
import java.net.URI
import java.net.URLEncoder
import java.net.http.HttpClient
import java.net.http.HttpRequest
import java.net.http.HttpResponse
import java.sql.DriverManager
import kotlin.test.*

/** SQLite móvil y Ktor reales contra Laravel y formularios web con sesión/CSRF en una BD desechable. */
class PortalProveedorPanelWebTest {
    private class Canal(private val real: ServidorWebRepository) : ServidorWebRepository by real {
        var conectado = true
        override suspend fun enviarCambio(usuarioId: String, entidad: String, cuerpo: JsonObject): Result<Long?> =
            if (conectado) real.enviarCambio(usuarioId, entidad, cuerpo) else Result.failure(SinConexionRemotaException())
    }

    private class Panel(private val url: String) {
        private val http = HttpClient.newBuilder().cookieHandler(CookieManager()).build()
        private var csrf = ""
        fun abrir(path: String): String {
            val respuesta = http.send(HttpRequest.newBuilder(URI.create(url + path)).GET().build(), HttpResponse.BodyHandlers.ofString())
            assertEquals(200, respuesta.statusCode(), path)
            csrf = Regex("name=\"_token\" value=\"([^\"]+)\"").find(respuesta.body())?.groupValues?.get(1) ?: csrf
            return respuesta.body()
        }
        fun enviar(path: String, datos: Map<String, String>) {
            val cuerpo = (datos + ("_token" to csrf)).entries.joinToString("&") {
                URLEncoder.encode(it.key, "UTF-8") + "=" + URLEncoder.encode(it.value, "UTF-8")
            }
            val respuesta = http.send(HttpRequest.newBuilder(URI.create(url + path))
                .header("Content-Type", "application/x-www-form-urlencoded")
                .header("Referer", url + "/admin/sanciones")
                .POST(HttpRequest.BodyPublishers.ofString(cuerpo)).build(), HttpResponse.BodyHandlers.ofString())
            assertEquals(302, respuesta.statusCode(), path)
        }
    }

    @Test
    fun `reclamo offline foto ajuste pendiente pago bloqueo y traslado recorren movil y web`() = runBlocking {
        val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
        val base = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()
        val pinAdmin = System.getenv("ECOLECTA_PIN_ADMIN").orEmpty()
        assumeTrue("Requiere backend y BD exclusivos de Coder 4", url == "http://127.0.0.1:8765" &&
            java.io.File(base).name.startsWith("coder4-pruebas-") && pinAdmin.isNotBlank())
        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) }
        val app = koinApplication {
            allowOverride(true)
            modules(dataModule, domainModule, module {
                single<SqlDriver> { driver }
                single<DeviceIdProvider> { object : DeviceIdProvider { override fun obtenerId() = "coder4-qa" } }
                single<RegistroAcopioRemotoRepository> { RegistroAcopioRemotoRepositoryPendiente() }
                single<pe.ecolecta.domain.security.ProtectorSecretos> { ProtectorSecretosPrueba }
                single<ServidorWebRepository> { Canal(ServidorWebRepositoryKtor(url, get(), get(), get(), get(), Dispatchers.IO, protector = get())) }
            })
        }
        try {
            val k = app.koin
            val servidor = k.get<ServidorWebRepository>() as Canal
            val local = k.get<DatosServidorLocalRepository>()
            suspend fun entrar(username: String, pin: String, rol: Rol): String {
                val remota = servidor.autenticar(username, pin).getOrThrow()
                val hash = Pbkdf2PinHasher().crearHash(pin)
                val id = local.guardarCuenta(remota.cuenta!!, hash.hash, hash.salt, 1)
                servidor.guardarSesion(id, remota)
                k.get<SesionRepository>().iniciar(Sesion(k.get<UsuarioRepository>().obtenerPorId(id)!!, rol))
                return id
            }
            val sufijo = java.util.UUID.randomUUID().toString()
            val ahora = System.currentTimeMillis()
            val acopiador = entrar("acop_faon", "2468", Rol.ACOPIADOR)
            val entregaId = "entrega-coder4-$sufijo"
            servidor.enviarEntrega(acopiador, EntregaParaServidor(
                entregaId, "jornada-coder4-$sufijo", ahora, null, "PRV-FAON-01", "FAON-MARKAPAJO",
                "V1A-123", "acop_faon", ahora, 20.0, 1, "Prueba Coder 4", false, null, ahora,
            )).getOrThrow()
            val usuarioId = entrar("prov_faon_01", "1234", Rol.PROVEEDOR)
            suspend fun descargar(): DatosServidor = servidor.descargarDatos(usuarioId).getOrThrow().also { local.aplicar(it) }
            val inicial = descargar()
            val proveedorServidor = inicial.proveedores.single { it.codigo == "PRV-FAON-01" }
            val proveedor = k.get<ProveedorRepository>().obtenerPorCodigo("PRV-FAON-01")!!
            val portal = k.get<PortalProveedorRepository>()
            val envio = k.get<EnviarCambiosServidorUseCase>()
            val panel = Panel(url)
            panel.abrir("/login")
            panel.enviar("/login", mapOf("username" to "admin", "pin" to pinAdmin))
            panel.abrir("/admin/sanciones")
            val fecha = fechaLima(ahora).toString()
            panel.enviar("/admin/liquidaciones", mapOf("proveedor_id" to proveedorServidor.id.toString(),
                "periodo_inicio" to fecha, "periodo_fin" to fecha, "precio_litro" to "1.805"))
            val pago = descargar().pagos!!.single { it.desde == fecha }
            assertEquals(36.10, pago.total)
            val foto = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aZ1kAAAAASUVORK5CYII="
            val solicitud = SolicitudProveedor("reclamo-coder4-$sufijo", proveedor.id, "RECLAMO", entregaId,
                25.0, "Litros mal registrados", "El comprobante tiene veinticinco litros.", foto, ahora)
            servidor.conectado = false
            portal.guardar(solicitud)
            assertTrue(envio(usuarioId).sinConexion)
            assertEquals("PENDIENTE_ENVIO", portal.solicitudes(proveedor.id).first().single().estado)
            descargar()
            assertEquals(foto, portal.solicitudes(proveedor.id).first().single().evidencia)
            servidor.conectado = true
            assertEquals(1, envio(usuarioId).enviados)
            assertEquals(0, envio(usuarioId).enviados)
            assertEquals("PENDIENTE", descargar().solicitudes!!.single().estado)
            fun idReclamo(uuid: String): Long = DriverManager.getConnection("jdbc:sqlite:$base").use { c ->
                c.prepareStatement("SELECT id FROM reclamos_proveedor WHERE uuid_movil = ?").use { q ->
                    q.setString(1, uuid)
                    q.executeQuery().use { rs -> assertTrue(rs.next()); rs.getLong(1) }
                }
            }
            val reclamoId = idReclamo(solicitud.id)
            assertTrue(panel.abrir("/admin/sanciones").contains("El comprobante tiene veinticinco litros."))
            panel.enviar("/admin/sanciones/reclamos/$reclamoId", mapOf("_method" to "PATCH", "estado" to "resuelto", "respuesta" to "Litros verificados y corregidos."))
            val aprobado = descargar()
            assertEquals(25.0, aprobado.entregas.single { it.uuidMovil == entregaId }.litros)
            assertEquals(45.13, aprobado.pagos!!.single().total)
            assertEquals("ATENDIDA", portal.solicitudes(proveedor.id).first().single().estado)
            assertEquals("Litros verificados y corregidos.", portal.solicitudes(proveedor.id).first().single().respuesta)
            assertEquals(foto, portal.solicitudes(proveedor.id).first().single().evidencia)
            panel.abrir("/admin/liquidaciones")
            panel.enviar("/admin/liquidaciones/${pago.id}/pagar", mapOf("_method" to "PATCH"))
            assertEquals("PAGADA", descargar().pagos!!.single().estado)
            assertEquals("PAGADA", portal.pagos(proveedor.id).first().single().estado)
            val segundo = solicitud.copy(id = "pagado-coder4-$sufijo", litros = 30.0)
            portal.guardar(segundo)
            assertEquals(1, envio(usuarioId).enviados)
            panel.abrir("/admin/sanciones")
            panel.enviar("/admin/sanciones/reclamos/${idReclamo(segundo.id)}", mapOf("_method" to "PATCH", "estado" to "resuelto", "respuesta" to "Intento de ajuste tras pago."))
            assertTrue(panel.abrir("/admin/sanciones").contains("liquidación pagada"))
            val bloqueado = descargar()
            assertEquals("PENDIENTE", bloqueado.solicitudes!!.single { it.id == segundo.id }.estado)
            assertEquals(25.0, bloqueado.entregas.single { it.uuidMovil == entregaId }.litros)
            val destino = inicial.zonas.first { it.id != proveedorServidor.zonaId }
            val zonaLocal = k.get<ZonaRepository>().observarTodas().first().single { it.nombre == destino.nombre }
            val traslado = SolicitudProveedor("traslado-coder4-$sufijo", proveedor.id, "TRASLADO", zonaLocal.id,
                null, "Traslado de zona", "Solicito cambiar mi punto de recojo.", creadaEn = ahora)
            portal.guardar(traslado)
            assertEquals(1, envio(usuarioId).enviados)
            assertEquals(proveedorServidor.zonaId, descargar().proveedores.single().zonaId)
            val trasladoId = DriverManager.getConnection("jdbc:sqlite:$base").use { c ->
                c.prepareStatement("SELECT id FROM solicitudes_traslado WHERE uuid_movil = ?").use { q ->
                    q.setString(1, traslado.id); q.executeQuery().use { rs -> assertTrue(rs.next()); rs.getLong(1) }
                }
            }
            // Regla vigente: 3 días de aviso. Se simula una solicitud hecha hace 4 días para que la aprobación aplique hoy.
            DriverManager.getConnection("jdbc:sqlite:$base").use { c ->
                c.prepareStatement("UPDATE solicitudes_traslado SET created_at = datetime('now', '-4 days') WHERE id = ?").use { q ->
                    q.setLong(1, trasladoId); q.executeUpdate()
                }
            }
            panel.abrir("/admin/organizacion/traslados")
            panel.enviar("/admin/organizacion/traslados/$trasladoId", mapOf("_method" to "PATCH", "estado" to "APROBADA", "respuesta" to "Ruta disponible; cambio autorizado."))
            assertEquals(destino.id, descargar().proveedores.single().zonaId)
            val resultado = portal.solicitudes(proveedor.id).first().single { it.id == traslado.id }
            assertEquals("APROBADA", resultado.estado)
            assertEquals(fechaLima(System.currentTimeMillis()).toString(), resultado.fechaEfectiva)
            assertNotNull(resultado.aplicadoEn)
            assertEquals("Ruta disponible; cambio autorizado.", resultado.respuesta)
        } finally {
            app.koin.get<io.ktor.client.HttpClient>().close()
            app.close()
            driver.close()
        }
    }
}
