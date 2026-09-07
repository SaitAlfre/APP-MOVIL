package pe.ecolecta.data.local

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.native.NativeSqliteDriver
import pe.ecolecta.data.local.db.EcolectaDatabase

actual class DatabaseDriverFactory {
    actual fun crearDriver(): SqlDriver =
        NativeSqliteDriver(EcolectaDatabase.Schema, "ecolecta.db")
}
