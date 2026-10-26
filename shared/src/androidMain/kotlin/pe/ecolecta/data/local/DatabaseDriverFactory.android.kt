package pe.ecolecta.data.local

import android.content.Context
import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.android.AndroidSqliteDriver

actual class DatabaseDriverFactory(private val context: Context) {
    actual fun crearDriver(): SqlDriver =
        AndroidSqliteDriver(SchemaCuentasSeguras, context, "ecolecta.db")
}
