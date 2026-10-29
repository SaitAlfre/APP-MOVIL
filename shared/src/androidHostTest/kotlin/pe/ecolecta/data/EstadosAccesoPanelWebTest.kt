package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import java.net.CookieManager
import java.net.URI
import java.net.URLEncoder
import java.net.http.HttpClient
import java.net.http.HttpRequest
import java.net.http.HttpResponse
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.runBlocking
import org.junit.Assume.assumeTrue
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.data.remote.crearHttpClient
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.RelojSistema
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.SinSesionServidorException
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** Cliente Kotlin real contra Laravel y una BD desechable; jamás se habilita contra el panel habitual. */
class EstadosAccesoPanelWebTest {
    @Test
    fun suspenderDesdeWebRevocaMovilYRestaurarRequiereAutenticacionNueva() = runBlocking<Unit> {
        val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
        val base = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()
        assumeTrue("Requiere Laravel aislado y seeders de prueba", url == "http://127.0.0.1:8765" && base.startsWith("/tmp/ecolactea-estados-ui-"))
        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
        EcolectaDatabase.Schema.create(driver)
        val db = EcolectaDatabase(driver)
        val reloj = RelojSistema()
        val usuarios = SqlDelightUsuarioRepository(db, Dispatchers.IO)
        val local = SqlDelightDatosServidorRepository(db, reloj, Dispatchers.IO)
        val http = crearHttpClient()
        val remoto = ServidorWebRepositoryKtor(url, http, db, usuarios, reloj, Dispatchers.IO, protector = ProtectorSecretosPrueba)
        val web = HttpClient.newBuilder().cookieHandler(CookieManager()).followRedirects(HttpClient.Redirect.NORMAL).build()
        fun pagina(ruta: String) = web.send(HttpRequest.newBuilder(URI.create(url + ruta)).GET().build(), HttpResponse.BodyHandlers.ofString())
        fun csrf(html: String) = Regex("name=\"_token\" value=\"([^\"]+)\"").find(html)?.groupValues?.get(1) ?: error("Falta CSRF de prueba")
        fun publicar(ruta: String, datos: Map<String, String>) = web.send(HttpRequest.newBuilder(URI.create(url + ruta))
            .header("Content-Type", "application/x-www-form-urlencoded")
            .POST(HttpRequest.BodyPublishers.ofString(datos.entries.joinToString("&") { (k, v) -> URLEncoder.encode(k, "UTF-8") + "=" + URLEncoder.encode(v, "UTF-8") })).build(), HttpResponse.BodyHandlers.ofString())
        var suspendida = false
        var idCuenta = 0L
        try {
            val tokenLogin = csrf(pagina("/login").body())
            val ingresoWeb = publicar("/login", mapOf("_token" to tokenLogin, "username" to "admin_estados", "pin" to "9753"))
            assertTrue(ingresoWeb.uri().path.startsWith("/admin/"))
            val tokenWeb = csrf(pagina("/admin/usuarios?buscar=prov_faon_01").body())
            val sesion = remoto.autenticar("prov_faon_01", "1234").getOrThrow()
            val cuenta = assertNotNull(sesion.cuenta)
            idCuenta = cuenta.id
            val hasher = Pbkdf2PinHasher()
            val par = hasher.crearHash("1234")
            val id = local.guardarCuenta(cuenta, par.hash, par.salt, 1)
            remoto.guardarSesion(id, sesion)
            db.sesionQueries.establecer(id, "PROVEEDOR")
            db.cambioPendienteQueries.marcar("proveedor", "ficha-offline", id, null, 1)
            val respuesta = publicar("/admin/usuarios/$idCuenta/acceso", mapOf("_token" to tokenWeb, "suspender" to "1", "motivo" to "Prueba integrada Kotlin-Laravel"))
            assertTrue(respuesta.body().contains("Acceso suspendido"))
            suspendida = true
            val rechazo = remoto.autenticar("prov_faon_01", "1234").exceptionOrNull()
            assertIs<RechazoServidorException>(rechazo)
            assertIs<SinSesionServidorException>(remoto.descargarDatos(id).exceptionOrNull())
            assertTrue(usuarios.obtenerPorId(id)!!.requiereVerificacion)
            assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
            assertNotNull(db.cambioPendienteQueries.porClave("proveedor", "ficha-offline").executeAsOneOrNull())
            val offline = LoginOfflineUseCase(usuarios, hasher, FakeAuditoriaRepository(), reloj, object : DeviceIdProvider { override fun obtenerId() = "integracion-estados" })
            assertIs<PinInvalidoException.RequiereConexion>(offline("prov_faon_01", "1234").exceptionOrNull())
            val restaurada = publicar("/admin/usuarios/$idCuenta/acceso", mapOf("_token" to tokenWeb, "suspender" to "0"))
            assertTrue(restaurada.body().contains("Acceso restaurado"))
            suspendida = false
            val refreshAnterior = web.send(HttpRequest.newBuilder(URI.create("$url/api/movil/sesion/refresh"))
                .header("Content-Type", "application/json").POST(HttpRequest.BodyPublishers.ofString("""{"refresh_token":"${sesion.refreshToken}"}""")).build(), HttpResponse.BodyHandlers.ofString())
            assertTrue(refreshAnterior.statusCode() == 401)
            val nueva = remoto.autenticar("prov_faon_01", "1234").getOrThrow()
            assertTrue(nueva.token != sesion.token)
            local.guardarCuenta(assertNotNull(nueva.cuenta), par.hash, par.salt, 2)
            remoto.guardarSesion(id, nueva)
            assertTrue(remoto.descargarDatos(id).isSuccess)
            assertFalse(usuarios.obtenerPorId(id)!!.requiereVerificacion)
            assertNotNull(db.cambioPendienteQueries.porClave("proveedor", "ficha-offline").executeAsOneOrNull())
            remoto.desvincular(id)
        } finally {
            if (suspendida) {
                val token = csrf(pagina("/admin/usuarios").body())
                publicar("/admin/usuarios/$idCuenta/acceso", mapOf("_token" to token, "suspender" to "0"))
            }
            http.close()
            driver.close()
        }
    }
}
