package pe.ecolecta.data

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
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
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.sync.CoordinadorSync
import java.sql.DriverManager
import kotlin.test.*

class CoordinadorPanelWebTest {
    private class Canal(private val real: ServidorWebRepository) : ServidorWebRepository by real {
        var conectado = true
        override suspend fun descargarDatos(usuarioId: String): Result<DatosServidor> =
            if (conectado) real.descargarDatos(usuarioId) else Result.failure(SinConexionRemotaException())
        override suspend fun enviarCambio(usuarioId: String, entidad: String, cuerpo: JsonObject): Result<Long?> =
            if (conectado) real.enviarCambio(usuarioId, entidad, cuerpo) else Result.failure(SinConexionRemotaException())
    }

    @Test fun `coordinador conserva offline envia una vez y descarga cambios web manualmente`() = runBlocking {
        val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
        val base = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()
        assumeTrue("Solo backend aislado en localhost", url == "http://127.0.0.1:8765" && base.isNotBlank())
        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) }
        val app = koinApplication {
            allowOverride(true)
            modules(dataModule, domainModule, module {
                single<SqlDriver> { driver }
                single<pe.ecolecta.domain.security.ProtectorSecretos> { ProtectorSecretosPrueba }
                single<RegistroAcopioRemotoRepository> { RegistroAcopioRemotoRepositoryPendiente() }
                single<ServidorWebRepository> { Canal(ServidorWebRepositoryKtor(url, get(), get(), get(), get(), Dispatchers.IO, protector = ProtectorSecretosPrueba)) }
            })
        }
        try {
            val k = app.koin
            val servidor = k.get<ServidorWebRepository>() as Canal
            val sesionRemota = servidor.autenticar("admin", System.getenv("ECOLECTA_PIN_ADMIN").orEmpty()).getOrThrow()
            val hash = Pbkdf2PinHasher().crearHash("1234")
            val id = k.get<DatosServidorLocalRepository>().guardarCuenta(sesionRemota.cuenta!!, hash.hash, hash.salt, 1)
            servidor.guardarSesion(id, sesionRemota)
            val usuario = k.get<UsuarioRepository>().obtenerPorId(id)!!
            k.get<SesionRepository>().iniciar(Sesion(usuario, Rol.ADMIN))
            val sync = k.get<CoordinadorSync>()
            sync.ejecutar(manual = true)
            assertTrue(sync.estado.value.verificado)
            val zona = Zona(java.util.UUID.randomUUID().toString(), "QA " + java.util.UUID.randomUUID().toString(), true)
            servidor.conectado = false
            k.get<ZonaRepository>().insertar(zona)
            sync.ejecutar(manual = true)
            assertTrue(sync.estado.value.sinConexion)
            assertTrue(sync.estado.value.pendientes > 0)
            assertEquals(zona.nombre, k.get<ZonaRepository>().obtenerPorId(zona.id)!!.nombre)
            servidor.conectado = true
            sync.ejecutar(manual = true)
            sync.ejecutar(manual = true)
            assertEquals(0, sync.estado.value.pendientes)
            val nuevoNombre = "WEB " + java.util.UUID.randomUUID().toString()
            DriverManager.getConnection("jdbc:sqlite:$base").use { conexion ->
                conexion.prepareStatement("SELECT COUNT(*) FROM zonas WHERE nombre = ?").use { q ->
                    q.setString(1, zona.nombre)
                    q.executeQuery().use { rs -> rs.next(); assertEquals(1, rs.getInt(1)) }
                }
                conexion.prepareStatement("UPDATE zonas SET nombre = ? WHERE nombre = ?").use { q ->
                    q.setString(1, nuevoNombre); q.setString(2, zona.nombre); assertEquals(1, q.executeUpdate())
                }
            }
            sync.automatico(false)
            sync.ejecutar()
            assertTrue(sync.estado.value.novedades)
            assertEquals(zona.nombre, k.get<ZonaRepository>().obtenerPorId(zona.id)!!.nombre)
            assertEquals("Sincronización completada", sync.ejecutar(manual = true), sync.estado.value.toString())
            assertEquals(nuevoNombre, k.get<ZonaRepository>().obtenerPorId(zona.id)!!.nombre)
            assertFalse(sync.estado.value.novedades)
            assertTrue(sync.estado.value.verificado)
        } finally {
            app.koin.get<io.ktor.client.HttpClient>().close()
            app.close()
            driver.close()
        }
    }
}
