package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.local.DatabaseSeeder
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightAuditoriaRepository
import pe.ecolecta.data.repository.SqlDelightEntregaRepository
import pe.ecolecta.data.repository.SqlDelightJornadaRepository
import pe.ecolecta.data.repository.SqlDelightProveedorRepository
import pe.ecolecta.data.repository.SqlDelightRegistroRecibidoRepository
import pe.ecolecta.data.repository.SqlDelightSinRecojoRepository
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.acopio.EstadoRecojo
import pe.ecolecta.domain.acopio.MotivoSinRecojo
import pe.ecolecta.domain.acopio.RegistroAcopioCompartido
import pe.ecolecta.domain.acopio.TipoRegistroCompartido
import pe.ecolecta.domain.acopio.cicloAcopioDe
import pe.ecolecta.domain.acopio.construirFilasAcopio
import pe.ecolecta.domain.acopio.hoyAcopio
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.acopio.DeshacerSinRecojoUseCase
import pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import java.io.File
import kotlin.io.path.createTempDirectory
import kotlin.test.AfterTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertTrue
import kotlin.time.Instant

/**
 * SQLite real en archivo (misma base que usa la app, vía el esquema de SQLDelight): cerrar y volver
 * a abrir el driver equivale a cerrar y reabrir la app. También migra una base "de la versión
 * anterior" con datos sembrados y comprueba que no se pierde ni cambia ninguna fila.
 */
class PersistenciaListaAcopioTest {
    private val carpeta: File = createTempDirectory("ecolecta-test").toFile()
    private val archivo = File(carpeta, "ecolecta.db")
    private val url = "jdbc:sqlite:${archivo.absolutePath}"

    // 2026-09-24 11:00 en Lima (UTC-5).
    private val reloj = object : Reloj {
        override fun hoy() = LocalDate(2026, 9, 24)
        override fun ahora() = Instant.parse("2026-09-24T16:00:00Z")
    }
    private val dispositivo = object : DeviceIdProvider { override fun obtenerId() = "celular-1" }

    @AfterTest fun limpiar() { carpeta.deleteRecursively() }

    /** Igual que AndroidSqliteDriver: crea o migra según `PRAGMA user_version`. */
    private fun abrir() = JdbcSqliteDriver(url, schema = EcolectaDatabase.Schema)

    @Test
    fun `sin recojo y entregas sobreviven a cerrar y reabrir la app`() = runTest {
        var driver = abrir()
        var db = EcolectaDatabase(driver)
        val proveedores = SqlDelightProveedorRepository(db, Dispatchers.Unconfined)
        val jornadas = SqlDelightJornadaRepository(db, Dispatchers.Unconfined)
        val entregas = SqlDelightEntregaRepository(db, Dispatchers.Unconfined)
        val marcas = SqlDelightSinRecojoRepository(db, Dispatchers.Unconfined)
        proveedores.insertar(proveedor("p1", "PRV-FAON-01"))
        proveedores.insertar(proveedor("p2", "PRV-FAON-02"))
        jornadas.insertar(Jornada("j1", "u1", "zona-faon-markapajo", "v1", LocalDate(2026, 9, 24), reloj.ahora().toEpochMilliseconds(), null, SyncState.PENDING))

        RegistrarEntregaUseCase(entregas, proveedores, reloj, dispositivo)("j1", "p1", "u1", "zona-faon-markapajo", "v1", 40.0, 1, null).getOrThrow()
        MarcarSinRecojoUseCase(jornadas, proveedores, entregas, marcas, reloj, dispositivo)(
            "j1", "p2", "u1", MotivoSinRecojo.SIN_LECHE, "vaca en parto",
        ).getOrThrow()
        driver.close()

        // "Reabrir la app": driver y repositorios nuevos sobre el mismo archivo.
        driver = abrir()
        db = EcolectaDatabase(driver)
        val entregas2 = SqlDelightEntregaRepository(db, Dispatchers.Unconfined)
        val marcas2 = SqlDelightSinRecojoRepository(db, Dispatchers.Unconfined)
        val proveedores2 = SqlDelightProveedorRepository(db, Dispatchers.Unconfined)
        val ciclo = cicloAcopioDe(hoyAcopio(reloj), "FAON-MARKAPAJO")
        val filas = construirFilasAcopio(
            ciclo, "zona-faon-markapajo",
            proveedores2.observarActivosPorZona("zona-faon-markapajo").first(),
            entregas2.filtrar(zonaId = "zona-faon-markapajo"),
            marcas2.observarPorZona("zona-faon-markapajo", ciclo.inicio, ciclo.fin).first(),
            remotoDisponible = true,
        )
        val hoy = LocalDate(2026, 9, 24)
        assertEquals(EstadoRecojo.REGISTRADO, filas.first { it.proveedor.id == "p1" }.dia(hoy)!!.estado)
        val sinRecojo = filas.first { it.proveedor.id == "p2" }.dia(hoy)!!
        assertEquals(EstadoRecojo.SIN_RECOJO, sinRecojo.estado)
        assertEquals("No tenía leche · vaca en parto", sinRecojo.sinRecojo!!.textoMotivo)

        // Deshacer tampoco borra: la marca queda deshecha, auditada y pendiente de sincronizar.
        val marca = marcas2.vigentePara("p2", hoy)!!
        DeshacerSinRecojoUseCase(SqlDelightJornadaRepository(db, Dispatchers.Unconfined), marcas2, reloj, dispositivo)(marca.id, "u1").getOrThrow()
        driver.close()

        driver = abrir()
        db = EcolectaDatabase(driver)
        val guardada = SqlDelightSinRecojoRepository(db, Dispatchers.Unconfined).obtenerPorId(marca.id)
        assertNotNull(guardada)
        assertTrue(guardada.deshecha)
        assertEquals(SyncState.PENDING, guardada.syncState)
        val auditoria = SqlDelightAuditoriaRepository(db, Dispatchers.Unconfined).filtrar(entidad = "sin_recojo")
        assertEquals(setOf(AccionAuditoria.CREAR, AccionAuditoria.ANULAR), auditoria.map { it.accion }.toSet())
        driver.close()
    }

    @Test
    fun `la cache del proveedor no duplica al recibir dos veces el mismo documento`() = runTest {
        val driver = abrir()
        val repo = SqlDelightRegistroRecibidoRepository(EcolectaDatabase(driver), Dispatchers.Unconfined)
        val doc = RegistroAcopioCompartido(
            id = "e1", tipo = TipoRegistroCompartido.ENTREGA, proveedorCodigo = "PRV-FAON-01", zonaId = "z", jornadaId = "j",
            acopiadorId = "u1", acopiadorNombre = "Juan", fecha = "2026-09-24", registradoEn = 1, litros = 40.0, tachos = 1, actualizadoEn = 10,
        )
        repo.guardar(listOf(doc), recibidoEn = 100)
        repo.guardar(listOf(doc.copy(litros = 38.0, actualizadoEn = 20)), recibidoEn = 200)
        repo.guardar(listOf(doc.copy(litros = 99.0, actualizadoEn = 5)), recibidoEn = 300) // copia vieja: no pisa

        val guardados = repo.observarPorCodigo("PRV-FAON-01").first()
        assertEquals(1, guardados.size)
        assertEquals(38.0, guardados.single().litros)
        assertTrue(repo.observarPorCodigo("PRV-FAON-02").first().isEmpty())
        driver.close()
    }

    @Test
    fun `migrar una base existente conserva todas sus filas y agrega las tablas nuevas`() = runTest {
        // 1) Base de la versión anterior: esquema actual SIN las tablas nuevas y con user_version anterior.
        val versionNueva = EcolectaDatabase.Schema.version
        var driver = JdbcSqliteDriver(url)
        EcolectaDatabase.Schema.create(driver)
        listOf("sin_recojo", "registro_recibido").forEach { driver.execute(null, "DROP TABLE $it", 0) }
        driver.execute(null, "PRAGMA user_version = ${versionNueva - 1}", 0)

        // 2) Datos reales de demostración: cuentas, fichas, jornada, entregas (incl. anulada y en
        //    conflicto), auditoría, traslado, calidad… más una liquidación y un análisis.
        val db = EcolectaDatabase(driver)
        DatabaseSeeder(db, reloj, Pbkdf2PinHasher()).sembrarSiEsNecesario()
        val proveedorId = db.proveedorQueries.selectTodos().executeAsList().first().id
        db.portalProveedorQueries.guardar("liq-1", proveedorId, "PAGO", """{"estado":"PAGADA","total":120.5}""", 1)
        val antes = instantanea(driver)
        assertTrue(antes.getValue("entrega").isNotEmpty(), "la base de prueba debe tener entregas")
        driver.close()

        // 3) Abrir con la app nueva: el driver ve user_version viejo y ejecuta la migración 11.
        driver = abrir()
        val despues = instantanea(driver)
        antes.forEach { (tabla, filas) -> assertEquals(filas, despues[tabla], "la tabla $tabla cambió al migrar") }
        assertTrue("sin_recojo" in despues && "registro_recibido" in despues)
        assertEquals(versionNueva, versionDe(driver))

        // 4) Las tablas nuevas funcionan sobre la base migrada.
        val migrada = EcolectaDatabase(driver)
        migrada.sinRecojoQueries.insertar("m1", "j", proveedorId, "u", "z", "2026-09-24", MotivoSinRecojo.OTRO.name, null, 1, 1)
        assertEquals(1L, migrada.sinRecojoQueries.contarPendientes().executeAsOne())
        driver.close()
    }

    private fun proveedor(id: String, codigo: String) = Proveedor(
        id = id, codigo = codigo, nombres = "Finca $codigo", dni = codigo, telefono = null, direccion = null,
        zonaId = "zona-faon-markapajo", tachos = 2, capacidadTachoL = 40.0, estado = EstadoProveedor.ACTIVO,
        updatedAt = 0, syncState = SyncState.SYNCED,
    )

    /** Todas las filas de todas las tablas, como texto, para comparar antes/después de migrar. */
    private fun instantanea(driver: JdbcSqliteDriver): Map<String, List<String>> {
        val tablas = driver.executeQuery(null, "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'", { c ->
            val nombres = mutableListOf<String>()
            while (c.next().value) nombres += c.getString(0)!!
            app.cash.sqldelight.db.QueryResult.Value(nombres)
        }, 0).value
        return tablas.associateWith { tabla ->
            val columnas = driver.executeQuery(null, "PRAGMA table_info($tabla)", { c ->
                var n = 0
                while (c.next().value) n++
                app.cash.sqldelight.db.QueryResult.Value(n)
            }, 0).value
            driver.executeQuery(null, "SELECT * FROM $tabla ORDER BY 1", { c ->
                val filas = mutableListOf<String>()
                while (c.next().value) filas += (0 until columnas).joinToString("|") { i -> c.getString(i) ?: "∅" }
                app.cash.sqldelight.db.QueryResult.Value(filas)
            }, 0).value
        }
    }

    private fun versionDe(driver: JdbcSqliteDriver): Long = driver.executeQuery(null, "PRAGMA user_version", { c ->
        c.next()
        app.cash.sqldelight.db.QueryResult.Value(c.getLong(0)!!)
    }, 0).value
}
