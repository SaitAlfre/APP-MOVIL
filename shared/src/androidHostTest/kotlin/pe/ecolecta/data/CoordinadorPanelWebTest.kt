package pe.ecolecta.data

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.runBlocking
import kotlinx.coroutines.flow.first
import kotlinx.serialization.json.*
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
    @Test fun `la API reemplaza el canal adicional y el proveedor recibe entregas no acopiado y su anulacion`() = runBlocking {
        val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
        val base = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()
        assumeTrue("Solo backend aislado en localhost", url == "http://127.0.0.1:8765" && base.isNotBlank())
        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) }
        val app = koinApplication {
            allowOverride(true)
            modules(dataModule, domainModule, module {
                single<SqlDriver> { driver }
                single<pe.ecolecta.domain.DeviceIdProvider> { object : pe.ecolecta.domain.DeviceIdProvider { override fun obtenerId() = "api-prueba" } }
                single<pe.ecolecta.domain.security.ProtectorSecretos> { ProtectorSecretosPrueba }
                single<ServidorWebRepository> { ServidorWebRepositoryKtor(url, get(), get(), get(), get(), Dispatchers.IO, protector = get()) }
                single<RegistroAcopioRemotoRepository> { pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryApi(
                    get(), get(), get(), get(), get(), get(), get(), get(), get(),
                    estado = { get<CoordinadorSync>().estado }) }
            })
        }
        try {
            val k = app.koin
            val servidor = k.get<ServidorWebRepository>()
            suspend fun entrar(username: String, pin: String, rol: Rol): Usuario {
                val remota = servidor.autenticar(username, pin).getOrThrow()
                val hash = Pbkdf2PinHasher().crearHash(pin)
                val id = k.get<DatosServidorLocalRepository>().guardarCuenta(remota.cuenta!!, hash.hash, hash.salt, 1)
                servidor.guardarSesion(id, remota)
                val usuario = k.get<UsuarioRepository>().obtenerPorId(id)!!
                k.get<SesionRepository>().iniciar(Sesion(usuario, rol))
                k.get<CoordinadorSync>().ejecutar(manual = true)
                return usuario
            }
            val admin = entrar("admin", System.getenv("ECOLECTA_PIN_ADMIN").orEmpty(), Rol.ADMIN)
            val sufijo = java.util.UUID.randomUUID().toString().take(8)
            val codigo = "QA-$sufijo"
            val username = "qa_prov_$sufijo"
            val dni = (System.nanoTime().mod(90_000_000L) + 10_000_000L).toString()
            servidor.enviarCambio(admin.id, "usuario", buildJsonObject {
                put("username", username); put("nombres", "Proveedor prueba API"); put("dni", dni)
                put("roles", buildJsonArray { add("proveedor") }); put("activo", true); put("pin", "1234")
            }).getOrThrow()
            servidor.enviarCambio(admin.id, "proveedor", buildJsonObject {
                put("codigo", codigo); put("nombres", "Proveedor prueba API"); put("dni", dni)
                put("telefono", JsonNull); put("direccion", JsonNull); put("zonaNombre", "FAON-MARKAPAJO")
                put("tachos", 2); put("capacidadTachoL", 40.0); put("estado", "ACTIVO"); put("usuarioUsername", username)
            }).getOrThrow()
            val acop = entrar("acop_faon", "2468", Rol.ACOPIADOR)
            val zona = k.get<ZonaRepository>().observarTodas().first().first { it.nombre == "FAON-MARKAPAJO" }
            val vehiculo = k.get<VehiculoRepository>().observarTodos().first().first { it.placa == "V1A-123" }
            val jornada = k.get<pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase>()(acop.id, zona.id, vehiculo.id).getOrThrow()
            val proveedor = k.get<ProveedorRepository>().obtenerPorCodigo(codigo)!!
            val marca = k.get<pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase>()(jornada.id, proveedor.id, acop.id,
                pe.ecolecta.domain.acopio.MotivoSinRecojo.SIN_LECHE, "Prueba sin Firebase").getOrThrow()
            k.get<CoordinadorSync>().ejecutar(manual = true)
            assertEquals(SyncState.SYNCED, k.get<SinRecojoRepository>().obtenerPorId(marca.id)!!.syncState)
            entrar(username, "1234", Rol.PROVEEDOR)
            val recibido = k.get<RegistroAcopioRemotoRepository>().observarDeProveedor(codigo).first()
            assertTrue(recibido is EventoRegistrosRemotos.Recibidos)
            assertTrue(recibido.registros.any { it.id == marca.id && !it.deshecha })
            entrar("acop_faon", "2468", Rol.ACOPIADOR)
            k.get<pe.ecolecta.domain.usecase.acopio.DeshacerSinRecojoUseCase>()(marca.id, acop.id).getOrThrow()
            val entrega = k.get<pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase>()(jornada.id, proveedor.id, acop.id,
                zona.id, vehiculo.id, 12.5, 1, null).getOrThrow().entrega
            k.get<CoordinadorSync>().ejecutar(manual = true)
            assertEquals(SyncState.SYNCED, k.get<EntregaRepository>().obtenerPorId(entrega.id)!!.syncState)
            entrar(username, "1234", Rol.PROVEEDOR)
            val final = k.get<RegistroAcopioRemotoRepository>().observarDeProveedor(codigo).first() as EventoRegistrosRemotos.Recibidos
            assertTrue(final.registros.any { it.id == marca.id && it.deshecha })
            assertTrue(final.registros.any { it.id == entrega.id && it.litros == 12.5 })
            assertEquals(1, final.registros.count { it.id == entrega.id })
        } finally {
            app.koin.get<io.ktor.client.HttpClient>().close()
            app.close()
            driver.close()
        }
    }

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
