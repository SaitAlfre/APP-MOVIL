package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import app.cash.turbine.test
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightSesionRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import kotlin.test.AfterTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFailsWith
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Instant

class SesionPersistidaTest {
    private val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
    private val db = EcolectaDatabase(driver.also { EcolectaDatabase.Schema.create(it) })
    private val usuarios = SqlDelightUsuarioRepository(db, Dispatchers.Unconfined)
    private val reloj = object : Reloj {
        override fun hoy() = LocalDate(2026, 10, 25)
        override fun ahora() = Instant.fromEpochMilliseconds(1_000)
    }
    private val sesiones = SqlDelightSesionRepository(db, usuarios, Dispatchers.Unconfined, reloj)

    init {
        db.usuarioQueries.insertar("cuenta", "operador", "Operador de prueba", "00000001", "hash", "salt", 1, 1)
        db.usuarioRolQueries.insertar("cuenta", "ADMIN")
    }

    @AfterTest
    fun cerrarBase() = driver.close()

    private suspend fun iniciar() {
        sesiones.iniciar(Sesion(assertNotNull(usuarios.obtenerPorId("cuenta")), Rol.ADMIN))
    }

    @Test
    fun seleccionarUnRolRetiradoDuranteElIngresoDevuelveFalloSinAbrirSesion() = runTest {
        val anterior = assertNotNull(usuarios.obtenerPorId("cuenta"))
        db.usuarioRolQueries.eliminar("cuenta", "ADMIN")
        val resultado = pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase(sesiones)(anterior, Rol.ADMIN)
        assertTrue(resultado.isFailure)
        assertNull(sesiones.observar().first())
    }

    @Test
    fun restauraUnaCuentaActivaConElRolAsignado() = runTest {
        iniciar()
        assertEquals("cuenta", sesiones.observar().first()?.usuario?.id)
    }

    @Test
    fun desactivarUnaCuentaRevocaLaSesionYReactivarlaNoLaResucita() = runTest {
        iniciar()
        sesiones.observar().test {
            assertNotNull(awaitItem())
            db.usuarioQueries.desactivar(2, "cuenta")
            assertNull(awaitItem())
            assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
            db.usuarioQueries.actualizar("Operador de prueba", "00000001", 1, 3, "cuenta")
            expectNoEvents()
            cancelAndIgnoreRemainingEvents()
        }
        assertNull(sesiones.observar().first())
    }

    @Test
    fun quitarElRolRevocaLaSesionSinCerrarLaAplicacion() = runTest {
        iniciar()
        sesiones.observar().test {
            assertNotNull(awaitItem())
            db.usuarioRolQueries.eliminar("cuenta", "ADMIN")
            assertNull(awaitItem())
            assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
            cancelAndIgnoreRemainingEvents()
        }
    }

    @Test
    fun unBloqueoTemporalInvalidaUnaSesionYaAbierta() = runTest {
        iniciar()
        sesiones.observar().test {
            assertNotNull(awaitItem())
            db.usuarioQueries.bloquear(2_000, 2, "cuenta")
            assertNull(awaitItem())
            cancelAndIgnoreRemainingEvents()
        }
    }

    @Test
    fun unRolGuardadoQueNoPerteneceALaCuentaNoDaAcceso() = runTest {
        db.sesionQueries.establecer("cuenta", "CALIDAD")
        assertNull(sesiones.observar().first())
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
    }

    @Test
    fun unaSesionConCuentaInexistenteSeDescartaSinBorrarDatosDeNegocio() = runTest {
        db.sesionQueries.establecer("cuenta-inexistente", "ADMIN")
        assertNull(sesiones.observar().first())
        assertNotNull(usuarios.obtenerPorId("cuenta"))
    }

    @Test
    fun iniciarRevalidaLaCuentaAunqueElObjetoRecibidoEsteDesactualizado() = runTest {
        val anterior = assertNotNull(usuarios.obtenerPorId("cuenta"))
        db.usuarioQueries.desactivar(2, "cuenta")
        assertFailsWith<IllegalArgumentException> { sesiones.iniciar(Sesion(anterior, Rol.ADMIN)) }
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
    }

    @Test
    fun cerrarSesionConservaLasCuentasYLosRoles() = runTest {
        iniciar()
        sesiones.cerrar()
        assertNull(sesiones.observar().first())
        assertEquals(listOf(Rol.ADMIN), usuarios.obtenerPorId("cuenta")?.roles)
    }
}
