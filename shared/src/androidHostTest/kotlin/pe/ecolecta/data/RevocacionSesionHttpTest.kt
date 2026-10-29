package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import com.sun.net.httpserver.HttpServer
import java.net.InetSocketAddress
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.runBlocking
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.data.remote.crearHttpClient
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.RelojSistema
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.SesionServidor
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinSesionServidorException
import kotlin.test.AfterTest
import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** HTTP real y SQLite aislada: una denegación de autorización nunca equivale a falta de Internet. */
class RevocacionSesionHttpTest {
    private val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
    private val db = EcolectaDatabase(driver.also { EcolectaDatabase.Schema.create(it) })
    private val reloj = RelojSistema()
    private val usuarios = SqlDelightUsuarioRepository(db, Dispatchers.IO)
    private val http = crearHttpClient()
    private val servidorHttp = HttpServer.create(InetSocketAddress("127.0.0.1", 0), 0)
    private var estado = 403
    private var codigo = "cuenta_suspendida"
    private val remoto: ServidorWebRepositoryKtor

    init {
        servidorHttp.createContext("/api/movil/") { peticion ->
            val cuerpo = """{"message":"Restricción ficticia","codigo":"$codigo"}""".toByteArray()
            peticion.responseHeaders.set("Content-Type", "application/json")
            peticion.sendResponseHeaders(estado, cuerpo.size.toLong())
            peticion.responseBody.use { it.write(cuerpo) }
        }
        servidorHttp.start()
        remoto = ServidorWebRepositoryKtor("http://127.0.0.1:${servidorHttp.address.port}", http, db, usuarios, reloj, Dispatchers.IO, protector = ProtectorSecretosPrueba)
    }

    private suspend fun preparar(): String {
        val local = SqlDelightDatosServidorRepository(db, reloj, Dispatchers.IO)
        val id = local.guardarCuenta(CuentaServidor(1, "prov_prueba", "Proveedor ficticio", "12345678", listOf("proveedor")), "hash-prueba", "salt-prueba", 1)
        remoto.guardarSesion(id, SesionServidor("token-prueba", Long.MAX_VALUE, null, "refresh-prueba", Long.MAX_VALUE))
        db.sesionQueries.establecer(id, "PROVEEDOR")
        db.cambioPendienteQueries.marcar("proveedor", "ficha-pendiente", id, null, 1)
        return id
    }

    @AfterTest
    fun cerrar() {
        servidorHttp.stop(0)
        http.close()
        driver.close()
    }

    @Test
    fun un403DeCuentaRevocaConfianzaLocalSinBorrarPendientes() = runBlocking<Unit> {
        val id = preparar()
        assertIs<SinSesionServidorException>(remoto.descargarDatos(id).exceptionOrNull())
        assertTrue(usuarios.obtenerPorId(id)!!.requiereVerificacion)
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
        assertNull(db.tokenServidorQueries.porUsuario(id).executeAsOneOrNull())
        assertNotNull(db.cambioPendienteQueries.porClave("proveedor", "ficha-pendiente").executeAsOneOrNull())
    }

    @Test
    fun un403ProductivoConservaSesionParaOtrosRolesYNoSeTrataComoFaltaDeRed() = runBlocking<Unit> {
        val id = preparar()
        codigo = "proveedor_no_activo"
        assertIs<RechazoServidorException>(remoto.descargarDatos(id).exceptionOrNull())
        assertFalse(usuarios.obtenerPorId(id)!!.requiereVerificacion)
        assertNotNull(db.sesionQueries.obtener().executeAsOneOrNull())
        assertNotNull(db.tokenServidorQueries.porUsuario(id).executeAsOneOrNull())
    }

    @Test
    fun un401ConRefreshRechazadoInvalidaSesionYConservaLaCola() = runBlocking<Unit> {
        val id = preparar()
        estado = 401
        codigo = "refresh_invalido"
        assertIs<SinSesionServidorException>(remoto.descargarDatos(id).exceptionOrNull())
        assertTrue(usuarios.obtenerPorId(id)!!.requiereVerificacion)
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
        assertNotNull(db.cambioPendienteQueries.porClave("proveedor", "ficha-pendiente").executeAsOneOrNull())
    }

    @Test
    fun sinRedConservaTokenSesionYPendientes() = runBlocking<Unit> {
        val id = preparar()
        servidorHttp.stop(0)
        assertIs<SinConexionRemotaException>(remoto.descargarDatos(id).exceptionOrNull())
        assertFalse(usuarios.obtenerPorId(id)!!.requiereVerificacion)
        assertNotNull(db.tokenServidorQueries.porUsuario(id).executeAsOneOrNull())
        assertNotNull(db.cambioPendienteQueries.porClave("proveedor", "ficha-pendiente").executeAsOneOrNull())
    }
}
