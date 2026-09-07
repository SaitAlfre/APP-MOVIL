package pe.ecolecta.data.local

import android.content.Context
import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.android.AndroidSqliteDriver
import pe.ecolecta.data.local.db.EcolectaDatabase

actual class DatabaseDriverFactory(private val context: Context) {
    actual fun crearDriver(): SqlDriver =
        AndroidSqliteDriver(EcolectaDatabase.Schema, context, "ecolecta.db")
}
