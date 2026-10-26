package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.local.SchemaCuentasSeguras
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightSesionRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

class MigracionRolesCoder1Test {
    private fun antigua(): JdbcSqliteDriver {
        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
        val recurso = checkNotNull(javaClass.getResource("/coder1/esquema-8.sql"))
        recurso.readText().replace(Regex("--[^\\n]*"), "").split(';').filter { it.isNotBlank() }
            .forEach { driver.execute(null, it, 0) }
        driver.execute(null, "INSERT INTO usuario(id,username,nombres,dni,pin_hash,pin_salt,updated_at) VALUES ('u','legado','Cuenta ficticia','00000001','hash-prueba','sal-prueba',1)", 0)
        driver.execute(null, "INSERT INTO usuario_rol VALUES ('u','ASISTENTE'),('u','DESPACHO'),('u','PRODUCCION'),('u','RECEPCION'),('u','LIQUIDACIONES'),('u','CONSULTA')", 0)
        driver.execute(null, "INSERT INTO zona(id,nombre,activo) VALUES ('z','ZONA FICTICIA',1)", 0)
        driver.execute(null, "INSERT INTO usuario_zona VALUES ('u','z')", 0)
        driver.execute(null, "INSERT INTO proveedor(id,codigo,nombres,dni,zona_id,updated_at,usuario_id) VALUES ('p','PRV-LEGADO','Ficha ficticia','00000002','z',1,'u')", 0)
        driver.execute(null, "INSERT INTO sesion_activa VALUES (0,'u','ASISTENTE')", 0)
        return driver
    }

    @Test
    fun desdeEsquema8ArchivaRolesExactosAntesDelBorradoHistoricoYConservaRelaciones() = runTest {
        antigua().use { driver ->
            SchemaCuentasSeguras.migrate(driver, 8, SchemaCuentasSeguras.version)
            comprobar(driver)
        }
    }

    @Test
    fun desdeEsquema10TambienProtegeAntesDeEjecutarLaMigracion10() = runTest {
        antigua().use { driver ->
            SchemaCuentasSeguras.migrate(driver, 8, 10)
            SchemaCuentasSeguras.migrate(driver, 10, SchemaCuentasSeguras.version)
            comprobar(driver)
        }
    }

    private suspend fun comprobar(driver: JdbcSqliteDriver) {
        val db = EcolectaDatabase(driver)
        val cuenta = db.usuarioQueries.selectPorId("u").executeAsOne()
        assertEquals("legado", cuenta.username)
        assertEquals("hash-prueba", cuenta.pin_hash)
        assertEquals(0L, cuenta.requiere_verificacion)
        assertEquals("z", db.usuarioZonaQueries.selectZonaId("u").executeAsOne())
        assertEquals("u", db.proveedorQueries.selectPorId("p").executeAsOne().usuario_id)
        assertEquals(setOf("ASISTENTE", "DESPACHO", "PRODUCCION", "RECEPCION", "LIQUIDACIONES", "CONSULTA"), db.usuarioRolPendienteQueries.selectTodos().executeAsList().map { it.rol_original }.toSet())
        assertTrue(db.usuarioRolQueries.selectPorUsuario("u").executeAsList().isEmpty())
        val usuarios = SqlDelightUsuarioRepository(db, Dispatchers.Unconfined)
        assertTrue(checkNotNull(usuarios.obtenerPorId("u")).roles.isEmpty(), "No se conceden roles por homologación inventada")
        assertNull(SqlDelightSesionRepository(db, usuarios, Dispatchers.Unconfined).observar().first())
        // Ensayo de reversibilidad SOLO en esta BD ficticia: el archivo conserva la asociación exacta.
        driver.execute(null, "INSERT OR IGNORE INTO usuario_rol(usuario_id,rol) SELECT usuario_id,rol_original FROM usuario_rol_pendiente", 0)
        assertEquals(setOf("ASISTENTE", "DESPACHO", "PRODUCCION", "RECEPCION", "LIQUIDACIONES", "CONSULTA"), db.usuarioRolQueries.selectPorUsuario("u").executeAsList().toSet())
        assertEquals("u", db.proveedorQueries.selectPorId("p").executeAsOne().usuario_id)
    }

    @Test
    fun instalacionNuevaNoInventaPerfilesPendientes() {
        JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).use { driver ->
            SchemaCuentasSeguras.create(driver)
            assertTrue(EcolectaDatabase(driver).usuarioRolPendienteQueries.selectTodos().executeAsList().isEmpty())
        }
    }
}
