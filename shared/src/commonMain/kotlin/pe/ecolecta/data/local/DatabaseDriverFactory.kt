package pe.ecolecta.data.local

import app.cash.sqldelight.db.SqlDriver

expect class DatabaseDriverFactory {
    fun crearDriver(): SqlDriver
}
