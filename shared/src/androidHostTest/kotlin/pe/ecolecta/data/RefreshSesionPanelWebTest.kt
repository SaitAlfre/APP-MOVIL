package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.async
import kotlinx.coroutines.awaitAll
import kotlinx.coroutines.coroutineScope
import kotlinx.coroutines.runBlocking
import org.junit.After
import org.junit.Before
import org.junit.Assume.assumeTrue
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.data.remote.crearHttpClient
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinSesionServidorException
import java.sql.DriverManager
import java.security.MessageDigest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Clock

/** Renovación real con Laravel; usa exclusivamente el servidor y la base de pruebas configurados. */
class RefreshSesionPanelWebTest {
    private val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
    private val baseWeb = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()
    private lateinit var driver: JdbcSqliteDriver
    private lateinit var db: EcolectaDatabase
    private val http = crearHttpClient()
    private val reloj = object : Reloj {
        override fun ahora() = Clock.System.now()
        override fun hoy() = kotlinx.datetime.LocalDate(2026, 10, 12)
    }
    private val usuarioId = "usuario-local-refresh"

    private fun servidor(direccion: String = url) = ServidorWebRepositoryKtor(
        direccion, http, db, SqlDelightUsuarioRepository(db, Dispatchers.IO), reloj, Dispatchers.IO,
    )

    @Before
    fun preparar() = runBlocking<Unit> {
        assumeTrue("Requiere backend aislado de pruebas en localhost:8765", url == "http://127.0.0.1:8765" && baseWeb.isNotBlank())
        driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
        EcolectaDatabase.Schema.create(driver)
        db = EcolectaDatabase(driver)
        servidor().vincular(usuarioId, "acop_faon", "2468").getOrThrow()
    }

    @After
    fun cerrar() = runBlocking<Unit> {
        if (::db.isInitialized) {
            servidor().desvincular(usuarioId)
            driver.close()
        }
        http.close()
    }

    @Test
    fun `renueva access vencido y persiste el par para otra instancia del cliente`() = runBlocking<Unit> {
        val anterior = db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne()
        assertNotNull(anterior.refresh_token)
        driver.execute(null, "UPDATE token_servidor SET expira_en = 0", 0)
        servidor().descargarDatos(usuarioId).getOrThrow()
        val nueva = db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne()
        assertNotEquals(anterior.token, nueva.token)
        assertNotEquals(anterior.refresh_token, nueva.refresh_token)
        assertEquals(anterior.refresh_expira_en, nueva.refresh_expira_en)
        driver.execute(null, "UPDATE token_servidor SET expira_en = 0", 0)
        servidor().descargarDatos(usuarioId).getOrThrow()
        assertNotEquals(nueva.refresh_token, db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne().refresh_token)
    }

    @Test
    fun `sin internet conserva refresh y renueva al recuperar la conexion`() = runBlocking<Unit> {
        val anterior = db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne()
        driver.execute(null, "UPDATE token_servidor SET expira_en = 0", 0)
        val fallo = servidor("http://127.0.0.1:9").descargarDatos(usuarioId)
        assertTrue(fallo.exceptionOrNull() is SinConexionRemotaException)
        assertEquals(anterior.refresh_token, db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne().refresh_token)
        servidor().descargarDatos(usuarioId).getOrThrow()
        assertNotEquals(anterior.token, db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne().token)
    }

    @Test
    fun `un 401 renueva una vez y un refresh revocado pide login`() = runBlocking<Unit> {
        val anterior = db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne()
        val hash = MessageDigest.getInstance("SHA-256").digest(anterior.token.toByteArray()).joinToString("") { "%02x".format(it) }
        DriverManager.getConnection("jdbc:sqlite:$baseWeb").use { conexion ->
            conexion.prepareStatement("UPDATE tokens_movil SET expira_en = '2000-01-01 00:00:00' WHERE token_hash = ?").use {
                it.setString(1, hash)
                assertEquals(1, it.executeUpdate())
            }
        }
        servidor().descargarDatos(usuarioId).getOrThrow()
        val nueva = db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne()
        assertNotEquals(anterior.token, nueva.token)
        val nuevoHash = MessageDigest.getInstance("SHA-256").digest(nueva.token.toByteArray()).joinToString("") { "%02x".format(it) }
        DriverManager.getConnection("jdbc:sqlite:$baseWeb").use { conexion ->
            conexion.prepareStatement("DELETE FROM refresh_tokens_movil WHERE token_movil_id IN (SELECT id FROM tokens_movil WHERE token_hash = ?)").use {
                it.setString(1, nuevoHash)
                it.executeUpdate()
            }
        }
        driver.execute(null, "UPDATE token_servidor SET expira_en = 0", 0)
        assertTrue(servidor().descargarDatos(usuarioId).exceptionOrNull() is SinSesionServidorException)
        assertNull(db.tokenServidorQueries.porUsuario(usuarioId).executeAsOneOrNull())
        DriverManager.getConnection("jdbc:sqlite:$baseWeb").use { conexion ->
            conexion.prepareStatement("DELETE FROM tokens_movil WHERE token_hash = ?").use {
                it.setString(1, nuevoHash)
                it.executeUpdate()
            }
        }
    }

    @Test
    fun `envios concurrentes del mismo cliente comparten una sola rotacion`() = runBlocking<Unit> {
        val cliente = servidor()
        driver.execute(null, "UPDATE token_servidor SET expira_en = 0", 0)
        coroutineScope { List(4) { async { cliente.descargarDatos(usuarioId).getOrThrow() } }.awaitAll() }
        assertNotNull(db.tokenServidorQueries.porUsuario(usuarioId).executeAsOne().refresh_token)
    }
}
