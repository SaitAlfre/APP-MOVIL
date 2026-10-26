package pe.ecolecta.data.local

import app.cash.sqldelight.db.AfterVersion
import app.cash.sqldelight.db.QueryResult
import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.db.SqlSchema
import pe.ecolecta.data.local.db.EcolectaDatabase

/**
 * Conserva las asociaciones antes de la migración histórica 10, sin reescribirla. Versiones que ya
 * ejecutaron aquel borrado necesitan una copia anterior para recuperar roles; no se adivinan.
 * El archivo no participa en autorización. La única sesión que se retira es la incompatible, como
 * hacía la migración original; usuarios, fichas y asignaciones permanecen.
 */
object SchemaCuentasSeguras : SqlSchema<QueryResult.Value<Unit>> by EcolectaDatabase.Schema {
    override fun migrate(driver: SqlDriver, oldVersion: Long, newVersion: Long, vararg callbacks: AfterVersion): QueryResult.Value<Unit> {
        if (oldVersion == 10L && newVersion > 10L) archivarRoles(driver)
        val proteccion = if (oldVersion <= 9L && newVersion > 10L) {
            arrayOf(AfterVersion(9L) { archivarRoles(it) })
        } else emptyArray()
        return EcolectaDatabase.Schema.migrate(driver, oldVersion, newVersion, *callbacks, *proteccion)
    }

    private fun archivarRoles(driver: SqlDriver) {
        driver.execute(null, """
            CREATE TABLE IF NOT EXISTS usuario_rol_pendiente (
                usuario_id TEXT NOT NULL, rol_original TEXT NOT NULL,
                PRIMARY KEY (usuario_id, rol_original)
            )
        """.trimIndent(), 0)
        driver.execute(null, """
            INSERT OR IGNORE INTO usuario_rol_pendiente(usuario_id, rol_original)
            SELECT usuario_id, rol FROM usuario_rol
            WHERE rol NOT IN ('ADMIN', 'ACOPIADOR', 'CALIDAD', 'PROVEEDOR')
        """.trimIndent(), 0)
    }
}
