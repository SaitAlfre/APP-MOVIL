package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.runBlocking
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.local.DatabaseSeeder
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightAuditoriaRepository
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.ComunicadoServidor
import pe.ecolecta.domain.repository.ControlServidor
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.EntregaServidor
import pe.ecolecta.domain.repository.JornadaServidor
import pe.ecolecta.domain.repository.ProveedorServidor
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SesionServidor
import pe.ecolecta.domain.repository.VehiculoServidor
import pe.ecolecta.domain.repository.ZonaServidor
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.IniciarSesionUseCase
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Clock
import kotlin.time.Instant

/**
 * Panel web -> celular sobre la base SQLite real de la app (con sus datos de prueba): lo que cambia en la
 * web reemplaza la copia del celular sin duplicar filas ni pisar lo que el celular aún no envió.
 */
class DatosPanelWebTest {
    private val reloj = object : Reloj {
        override fun hoy() = LocalDate(2026, 9, 24)
        override fun ahora(): Instant = Clock.System.now()
    }
    private val db = EcolectaDatabase(JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) })
        .also { DatabaseSeeder(it, reloj, Pbkdf2PinHasher()).sembrarSiEsNecesario() }
    private val local = SqlDelightDatosServidorRepository(db, reloj, kotlinx.coroutines.Dispatchers.IO)

    private val zonaFaon = db.zonaQueries.selectTodas().executeAsList().first { it.nombre == "FAON-MARKAPAJO" }
    private val acopFaon = db.usuarioQueries.selectPorUsername("acop_faon").executeAsOne()
    private val provFaon = db.proveedorQueries.selectPorCodigo("PRV-FAON-01").executeAsOne()

    private fun datos(
        zonaNombre: String = "FAON-MARKAPAJO",
        litros: Double = 40.0,
        anulada: Boolean = false,
        comunicados: List<ComunicadoServidor> = listOf(ComunicadoServidor(7, "Aviso", "Limpiar tachos", "Admin Web", 1_000)),
    ) = DatosServidor(
        completo = true,
        zonas = listOf(ZonaServidor(1, zonaNombre), ZonaServidor(2, "ZONA SOLO WEB")),
        vehiculos = listOf(VehiculoServidor(1, "Isuzu", "d4m-101")),
        usuarios = listOf(
            CuentaServidor(10, "acop_faon", "Juan Carlos Mamani Quispe", "99002001", listOf("acopiador")),
            CuentaServidor(11, "nuevo_web", "Rosa Apaza Huanca", "99005555", listOf("proveedor")),
        ),
        proveedores = listOf(
            ProveedorServidor(20, "PRV-FAON-01", "Nombre cambiado en la web", provFaon.dni, zonaId = 1),
            ProveedorServidor(21, "DEMO-PE-99", "Rosa Apaza Huanca", "99005555", zonaId = 2, tachos = 3, usuarioId = 11),
        ),
        jornadas = listOf(JornadaServidor(30, null, 10, 1, 1, "2026-09-23", 1_000, 2_000)),
        entregas = listOf(EntregaServidor(40, null, 30, 21, 10, 2, 1, litros, 1, null, 1_500, anulada)),
        controles = listOf(ControlServidor(50, proveedorId = 21, usuarioId = 10, resultado = "RECHAZADO", temperatura = 12.0, acidez = 23.0, evaluadoEn = 1_600)),
        comunicados = comunicados,
    )

    @Test
    fun `lo que se cambia en la web aparece en el celular sin duplicar`() = runBlocking {
        local.aplicar(datos())
        local.aplicar(datos()) // repetir no duplica

        // Zonas: la del celular se reconoce por nombre; la nueva se crea; al renombrar en la web se sigue la misma fila.
        assertEquals(1, db.zonaQueries.selectTodas().executeAsList().count { it.nombre == "FAON-MARKAPAJO" })
        local.aplicar(datos(zonaNombre = "FAON RENOMBRADA"))
        assertEquals("FAON RENOMBRADA", db.zonaQueries.selectPorId(zonaFaon.id).executeAsOne().nombre)
        // Copia completa del admin: una zona que el panel ya no tiene queda inactiva.
        val otras = db.zonaQueries.selectTodas().executeAsList().filter { it.id != zonaFaon.id && it.nombre != "ZONA SOLO WEB" }
        assertTrue(otras.all { it.activo == 0L })

        // Vehículo nuevo con placa normalizada y proveedor existente actualizado.
        assertNotNull(db.vehiculoQueries.selectPorPlaca("D4M-101").executeAsOneOrNull())
        assertEquals("Nombre cambiado en la web", db.proveedorQueries.selectPorId(provFaon.id).executeAsOne().nombres)

        // Cuenta nueva de la web: existe con su rol, vinculada a su ficha, pero sin PIN local todavía.
        val nuevo = db.usuarioQueries.selectPorUsername("nuevo_web").executeAsOne()
        assertEquals("", nuevo.pin_hash)
        assertEquals(listOf(Rol.PROVEEDOR.name), db.usuarioRolQueries.selectPorUsuario(nuevo.id).executeAsList())
        assertEquals(nuevo.id, db.proveedorQueries.selectPorCodigo("DEMO-PE-99").executeAsOne().usuario_id)

        // Entrega registrada en la web, luego corregida y anulada allí.
        val entregaId = "web-entrega-40"
        assertEquals(40.0, db.entregaQueries.selectPorId(entregaId).executeAsOne().litros)
        local.aplicar(datos(litros = 35.5, anulada = true))
        val corregida = db.entregaQueries.selectPorId(entregaId).executeAsOne()
        assertEquals(35.5, corregida.litros)
        assertEquals(1L, corregida.anulada)
        assertEquals("SYNCED", corregida.sync_state)

        // Calidad y comunicados; un comunicado despublicado en la web desaparece del celular.
        assertEquals("RECHAZADO", db.controlCalidadQueries.selectPorId("web-control-50").executeAsOne().estado)
        assertEquals(1, db.comunicadoQueries.selectTodos().executeAsList().count { it.id == "web-comunicado-7" })
        local.aplicar(datos(comunicados = emptyList()))
        assertTrue(db.comunicadoQueries.selectTodos().executeAsList().none { it.id.startsWith("web-comunicado-") })
    }

    @Test
    fun `una entrega que el celular aun no envio no se pisa con la copia del panel`() = runBlocking {
        local.aplicar(datos())
        db.entregaQueries.corregir(litros = 50.0, tachos = 2, observaciones = "corregida aquí", updated_at = 9_999, id = "web-entrega-40")

        local.aplicar(datos(litros = 10.0))

        val entrega = db.entregaQueries.selectPorId("web-entrega-40").executeAsOne()
        assertEquals(50.0, entrega.litros)
        assertEquals("PENDING", entrega.sync_state)
    }

    @Test
    fun `una cuenta creada en la web entra con su PIN y queda guardada para entrar sin conexion`() = runBlocking {
        val usuarios = SqlDelightUsuarioRepository(db, kotlinx.coroutines.Dispatchers.IO)
        val hasher = Pbkdf2PinHasher()
        var tokenGuardado: String? = null
        val servidor = object : ServidorWebRepository {
            override val configurado = true
            override suspend fun vincular(usuarioId: String, username: String, pin: String) = Result.success(Unit)
            override fun observarSesion(usuarioId: String): Flow<Boolean> = flowOf(true)
            override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor) = Result.success(Unit)
            override suspend fun liquidacionesDelProveedor(usuarioId: String) = Result.success(emptyList<PagoProveedor>())
            override suspend fun desvincular(usuarioId: String) = Unit
            override suspend fun autenticar(username: String, pin: String): Result<SesionServidor> =
                if (username == "demo_calidad" && pin == "1234") {
                    Result.success(SesionServidor("tok", Long.MAX_VALUE, CuentaServidor(99, "demo_calidad", "Rosa Luz Apaza Huanca", "99001001", listOf("calidad", "produccion"))))
                } else {
                    Result.failure(IllegalStateException("credenciales"))
                }
            override suspend fun guardarSesion(usuarioId: String, sesion: SesionServidor) { tokenGuardado = usuarioId }
        }
        val dispositivo = object : DeviceIdProvider { override fun obtenerId() = "celular" }
        val offline = LoginOfflineUseCase(usuarios, hasher, SqlDelightAuditoriaRepository(db, kotlinx.coroutines.Dispatchers.IO), reloj, dispositivo)
        val iniciar = IniciarSesionUseCase(offline, servidor, local, usuarios, hasher, reloj)

        assertTrue(iniciar("demo_calidad", "9999").isFailure, "un PIN que el panel rechaza no entra")
        val inicio = iniciar("demo_calidad", "1234").getOrThrow()

        assertEquals(listOf(Rol.CALIDAD), inicio.usuario.roles)
        assertTrue(inicio.enlazado)
        assertEquals(inicio.usuario.id, tokenGuardado)
        // La próxima vez entra sin red con el mismo PIN.
        assertEquals(inicio.usuario.id, offline("demo_calidad", "1234").getOrThrow().id)
        assertNull(db.usuarioQueries.selectPorId(inicio.usuario.id).executeAsOne().bloqueado_hasta)
    }
}
