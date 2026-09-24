package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.runBlocking
import kotlinx.datetime.LocalDate
import org.junit.Assume.assumeTrue
import pe.ecolecta.data.local.DatabaseSeeder
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.data.remote.crearHttpClient
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente
import pe.ecolecta.data.repository.SqlDelightAuditoriaRepository
import pe.ecolecta.data.repository.SqlDelightEntregaRepository
import pe.ecolecta.data.repository.SqlDelightGestionPortalRepository
import pe.ecolecta.data.repository.SqlDelightJornadaRepository
import pe.ecolecta.data.repository.SqlDelightProveedorRepository
import pe.ecolecta.data.repository.SqlDelightSinRecojoRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.data.repository.SqlDelightVehiculoRepository
import pe.ecolecta.data.repository.SqlDelightZonaRepository
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.ultimaLiquidacionEmitida
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega
import pe.ecolecta.domain.usecase.sync.PreparadorEntregaServidorLocal
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import java.sql.DriverManager
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertTrue
import kotlin.time.Clock
import kotlin.time.Instant

/**
 * Integración REAL app → panel web: base SQLite de la app, casos de uso reales y el cliente Ktor real
 * contra un servidor Laravel en ejecución; la verificación se hace leyendo la base que consulta el panel.
 *
 * Se omite si no se indica el servidor. Para ejecutarla (con una base de prueba, nunca la del usuario):
 *   ECOLECTA_SERVIDOR_PRUEBA=http://127.0.0.1:8765 ECOLECTA_SERVIDOR_BD=/ruta/web-prueba.sqlite \
 *     ./gradlew :shared:testAndroidHostTest --tests '*IntegracionPanelWebTest'
 * La base del servidor debe tener los datos de `php artisan db:seed --class=MovilDemoSeeder`.
 */
class IntegracionPanelWebTest {
    private val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
    private val baseWeb = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()

    private val reloj = object : Reloj {
        override fun hoy() = LocalDate(2026, 9, 24)
        override fun ahora(): Instant = Clock.System.now()
    }
    private val dispositivo = object : DeviceIdProvider { override fun obtenerId() = "celular-integracion" }

    private fun <T> consultarWeb(sql: String, vararg parametros: Any, leer: (java.sql.ResultSet) -> T): List<T> =
        DriverManager.getConnection("jdbc:sqlite:$baseWeb").use { conexion ->
            conexion.prepareStatement(sql).use { consulta ->
                parametros.forEachIndexed { i, p -> consulta.setObject(i + 1, p) }
                consulta.executeQuery().use { filas -> buildList { while (filas.next()) add(leer(filas)) } }
            }
        }

    @Test
    fun `entregas, reintentos, correcciones y anulaciones llegan a la base del panel web`() = runBlocking {
        assumeTrue("Sin servidor de prueba (ECOLECTA_SERVIDOR_PRUEBA / ECOLECTA_SERVIDOR_BD)", url.isNotBlank() && baseWeb.isNotBlank())

        // --- Celular del acopiador: base local real con los datos de prueba de la app ---
        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) }
        val db = EcolectaDatabase(driver)
        DatabaseSeeder(db, reloj, Pbkdf2PinHasher()).sembrarSiEsNecesario()
        val io = Dispatchers.IO
        val usuarios = SqlDelightUsuarioRepository(db, io)
        val proveedores = SqlDelightProveedorRepository(db, io)
        val jornadas = SqlDelightJornadaRepository(db, io)
        val entregas = SqlDelightEntregaRepository(db, io)
        val auditoria = SqlDelightAuditoriaRepository(db, io)
        val zonas = SqlDelightZonaRepository(db, io)
        val vehiculos = SqlDelightVehiculoRepository(db, io)
        val regla = ReglaEdicionEntrega(SqlDelightGestionPortalRepository(db, io))

        val acopiador = db.usuarioQueries.selectPorUsername("acop_faon").executeAsOne()
        val proveedor = assertNotNull(proveedores.obtenerPorCodigo("PRV-FAON-01"))
        val zona = db.zonaQueries.selectTodas().executeAsList().first { it.nombre == "FAON-MARKAPAJO" }
        val vehiculo = db.vehiculoQueries.selectTodos().executeAsList().first { it.placa == "V1A-123" }
        val jornadaId = "jornada-integracion-${System.currentTimeMillis()}"
        jornadas.insertar(Jornada(jornadaId, acopiador.id, zona.id, vehiculo.id, reloj.hoy(), reloj.ahora().toEpochMilliseconds(), null, SyncState.PENDING))

        val http = crearHttpClient()
        val servidor = ServidorWebRepositoryKtor(url, http, db, usuarios, reloj, io, "Prueba de integración")
        val sincronizar = SincronizarRegistrosAcopioUseCase(
            entregas, SqlDelightSinRecojoRepository(db, io), proveedores, usuarios, RegistroAcopioRemotoRepositoryPendiente(),
            servidor, PreparadorEntregaServidorLocal(proveedores, usuarios, jornadas, zonas, vehiculos, auditoria),
        )

        // 1) Sin sesión con el panel: queda en error visible, nada llega.
        val entrega = RegistrarEntregaUseCase(entregas, proveedores, reloj, dispositivo)(
            jornadaId, proveedor.id, acopiador.id, zona.id, vehiculo.id, 38.5, 2, "Integración",
        ).getOrThrow().entrega
        sincronizar()
        assertEquals(SyncState.ERROR, entregas.obtenerPorId(entrega.id)!!.syncState)

        // 2) Login (mismo usuario y PIN que en la app) → token → envío real.
        servidor.vincular(acopiador.id, "acop_faon", "2468").getOrThrow()
        val r = sincronizar()
        assertEquals(1, r.enviados)
        assertEquals(SyncState.SYNCED, entregas.obtenerPorId(entrega.id)!!.syncState)

        val sqlFila = """
            SELECT e.litros, e.tachos, e.anulada, e.registrado_en, p.codigo, z.nombre, v.placa, u.username
            FROM entregas e JOIN proveedores p ON p.id = e.proveedor_id JOIN zonas z ON z.id = e.zona_id
            JOIN vehiculos v ON v.id = e.vehiculo_id JOIN usuarios u ON u.id = e.usuario_id WHERE e.uuid_movil = ?
        """.trimIndent()
        fun filaWeb() = consultarWeb(sqlFila, entrega.id) {
            listOf(it.getDouble(1), it.getInt(2), it.getInt(3), it.getString(4), it.getString(5), it.getString(6), it.getString(7), it.getString(8))
        }
        val registradoUtc = Instant.fromEpochMilliseconds(entrega.registradoEn).toString().replace("T", " ").substring(0, 19)
        assertEquals(listOf(listOf(38.5, 2, 0, registradoUtc, "PRV-FAON-01", "FAON-MARKAPAJO", "V1A-123", "acop_faon")), filaWeb())

        // 3) Reintentar no duplica.
        entregas.registrarFalloSync(entrega.id, entregas.obtenerPorId(entrega.id)!!.updatedAt, "forzar reenvío", true)
        sincronizar()
        assertEquals(1, consultarWeb("SELECT COUNT(*) FROM entregas WHERE uuid_movil = ?", entrega.id) { it.getInt(1) }.single())

        // 4) Corrección y anulación llegan a la MISMA fila, con auditoría en el panel.
        CorregirEntregaUseCase(entregas, reloj, dispositivo, regla)(entrega.id, 36.0, 2, "Integración", "Medida mal leída", acopiador.id).getOrThrow()
        sincronizar()
        assertEquals(36.0, filaWeb().single()[0])
        AnularEntregaUseCase(entregas, reloj, dispositivo, regla)(entrega.id, "Registro duplicado", acopiador.id).getOrThrow()
        sincronizar()
        assertEquals(1, filaWeb().single()[2])
        assertEquals(SyncState.SYNCED, entregas.obtenerPorId(entrega.id)!!.syncState)
        val auditoriaWeb = consultarWeb(
            "SELECT a.accion, a.motivo FROM auditorias a JOIN entregas e ON e.id = a.entidad_id WHERE a.entidad = 'entrega' AND e.uuid_movil = ? ORDER BY a.id",
            entrega.id,
        ) { it.getString(1) to it.getString(2) }
        assertEquals(listOf("crear", "corregir", "anular"), auditoriaWeb.map { it.first })
        assertEquals("Medida mal leída", auditoriaWeb[1].second)
        assertEquals("Registro duplicado", auditoriaWeb[2].second)

        // 5) Servidor inalcanzable: la entrega nueva queda pendiente (no "sincronizada") y el dato se conserva.
        // Mismo token, pero el servidor no responde (puerto cerrado).
        val caido = ServidorWebRepositoryKtor("http://127.0.0.1:9", http, db, usuarios, reloj, io)
        val otra = RegistrarEntregaUseCase(entregas, proveedores, reloj, dispositivo)(
            jornadaId, proveedor.id, acopiador.id, zona.id, vehiculo.id, 12.0, 1, null,
        ).getOrThrow().entrega
        SincronizarRegistrosAcopioUseCase(
            entregas, SqlDelightSinRecojoRepository(db, io), proveedores, usuarios, RegistroAcopioRemotoRepositoryPendiente(),
            caido, PreparadorEntregaServidorLocal(proveedores, usuarios, jornadas, zonas, vehiculos, auditoria),
        )()
        assertEquals(SyncState.PENDING, entregas.obtenerPorId(otra.id)!!.syncState)
        assertEquals(0, consultarWeb("SELECT COUNT(*) FROM entregas WHERE uuid_movil = ?", otra.id) { it.getInt(1) }.single())
        sincronizar()
        assertEquals(1, consultarWeb("SELECT COUNT(*) FROM entregas WHERE uuid_movil = ?", otra.id) { it.getInt(1) }.single())

        // 6) Web → proveedor: sus liquidaciones con su estado real, iguales a las del panel, y nunca las de otro.
        val cuentaProveedor = db.usuarioQueries.selectPorUsername("prov_faon_01").executeAsOne()
        servidor.vincular(cuentaProveedor.id, "prov_faon_01", "1234").getOrThrow()
        val recibidas = servidor.liquidacionesDelProveedor(cuentaProveedor.id).getOrThrow()
        val delPanel = consultarWeb(
            "SELECT l.periodo_inicio, l.precio_litro, l.monto_total, l.estado FROM liquidaciones l JOIN proveedores p ON p.id = l.proveedor_id WHERE p.codigo = 'PRV-FAON-01' ORDER BY l.periodo_inicio DESC",
        ) { listOf(it.getString(1).take(10), it.getDouble(2), it.getDouble(3), if (it.getString(4) == "pagada") "PAGADA" else "PENDIENTE") }
        assertTrue(delPanel.isNotEmpty(), "la base de prueba debe tener liquidaciones de PRV-FAON-01")
        assertEquals(delPanel, recibidas.map { listOf(it.desde, it.precio, it.total, it.estado) }, "mismos periodos, importes y estado real que el panel")
        val ultima = assertNotNull(ultimaLiquidacionEmitida(recibidas))
        assertEquals(delPanel.first()[3], ultima.estado, "Inicio resume la más reciente con su estado real")
        assertTrue(servidor.liquidacionesDelProveedor(acopiador.id).isFailure, "un acopiador no puede leer liquidaciones de proveedor")

        http.close()
        driver.close()
    }
}
