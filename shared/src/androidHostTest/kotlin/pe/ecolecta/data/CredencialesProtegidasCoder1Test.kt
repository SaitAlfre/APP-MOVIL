package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebNoConfigurado
import pe.ecolecta.data.repository.SqlDelightCambiosLocalesRepository
import pe.ecolecta.data.security.ProteccionCredencialesLocales
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.security.ProtectorSecretos
import pe.ecolecta.domain.security.SecretoLocalNoDisponibleException
import pe.ecolecta.domain.usecase.sync.EnviarCambiosServidorUseCase
import kotlinx.serialization.json.jsonPrimitive
import kotlin.test.*

class CredencialesProtegidasCoder1Test {
    private val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
    private val db = EcolectaDatabase(driver.also { EcolectaDatabase.Schema.create(it) })
    private val protector = ProtectorSecretosPrueba
    private val cola = SqlDelightCambiosLocalesRepository(db, Dispatchers.Unconfined, protector)

    private fun cuenta() {
        db.usuarioQueries.insertar("u", "ficticio", "Cuenta ficticia", "00000001", "hash-ficticio", "sal-ficticia", 1, 1)
        db.usuarioRolQueries.insertar("u", "CALIDAD")
        db.sesionQueries.establecer("u", "CALIDAD")
    }

    @AfterTest fun cerrar() = driver.close()

    @Test fun pinSeCifraEnReposoSeAbreSoloParaElEnvioYSeEliminaAlConfirmar() = runTest {
        cuenta()
        cola.recordarPin("u", "2468")
        val fila = db.cambioPendienteQueries.porClave("usuario", "u").executeAsOne()
        assertNotEquals("2468", fila.pin)
        assertTrue(checkNotNull(fila.pin).startsWith("ks1:"))
        val cambio = cola.pendientes().single()
        assertEquals("2468", assertNotNull(cambio.cuerpo).getValue("pin").jsonPrimitive.content)
        cola.completar(cambio, 10)
        assertNull(db.cambioPendienteQueries.porClave("usuario", "u").executeAsOneOrNull())
        assertNotNull(db.usuarioQueries.selectPorId("u").executeAsOneOrNull())
    }

    @Test fun conversionAnteriorEsIdempotenteYConservaErroresYVigencias() {
        cuenta()
        db.cambioPendienteQueries.marcar("usuario", "u", "u", "2468", 1)
        db.cambioPendienteQueries.registrarFallo("ERROR", "Revisar", "usuario", "u", 1)
        db.tokenServidorQueries.guardarConRefresh("u", "access-ficticio", 100, 1, "refresh-ficticio", 200)
        val convertir = ProteccionCredencialesLocales(db, protector)
        convertir.aplicar()
        val pin = db.cambioPendienteQueries.porClave("usuario", "u").executeAsOne()
        val token = db.tokenServidorQueries.porUsuario("u").executeAsOne()
        assertEquals("ERROR", pin.estado)
        assertEquals("Revisar", pin.ultimo_error)
        assertEquals("2468", protector.abrir(checkNotNull(pin.pin), "pin:u"))
        assertEquals("access-ficticio", protector.abrir(token.token, "access:u"))
        assertEquals("refresh-ficticio", protector.abrir(checkNotNull(token.refresh_token), "refresh:u"))
        assertEquals(100L, token.expira_en)
        assertEquals(200L, token.refresh_expira_en)
        convertir.aplicar()
        assertEquals(pin, db.cambioPendienteQueries.porClave("usuario", "u").executeAsOne())
        assertEquals(token, db.tokenServidorQueries.porUsuario("u").executeAsOne())
    }

    @Test fun credencialIlegibleNoBorraLaColaNiSeEnviaComoSiFueraNula() = runTest {
        cuenta()
        cola.recordarPin("u", "2468")
        val cifrado = db.cambioPendienteQueries.porClave("usuario", "u").executeAsOne().pin
        val sinClave = SqlDelightCambiosLocalesRepository(db, Dispatchers.Unconfined, ProtectorSecretos.NoDisponible)
        val servidor = object : ServidorWebRepository by ServidorWebNoConfigurado() { override val configurado = true }
        assertEquals(1, EnviarCambiosServidorUseCase(servidor, sinClave)().conError)
        val fila = db.cambioPendienteQueries.porClave("usuario", "u").executeAsOne()
        assertEquals("ERROR", fila.estado)
        assertEquals(cifrado, fila.pin)
        assertTrue(fila.ultimo_error.orEmpty().contains("Se conserva"))
    }

    @Test fun cifradoAlteradoUOtraCuentaNoAbrenElSecreto() {
        val cifrado = protector.proteger("ficticio", "pin:u")
        assertFailsWith<SecretoLocalNoDisponibleException> { protector.abrir(cifrado, "pin:otra") }
        assertFailsWith<SecretoLocalNoDisponibleException> { protector.abrir("ks1:invalido:invalido", "pin:u") }
        assertNotEquals(cifrado, protector.proteger("ficticio", "pin:u"))
    }
}
