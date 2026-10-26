package pe.ecolecta.data.local

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.native.NativeSqliteDriver

actual class DatabaseDriverFactory {
    actual fun crearDriver(): SqlDriver =
        NativeSqliteDriver(SchemaCuentasSeguras, "ecolecta.db")
}
