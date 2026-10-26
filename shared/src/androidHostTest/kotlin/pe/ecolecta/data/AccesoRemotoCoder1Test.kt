package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebNoConfigurado
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.RelojSistema
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SesionServidor
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.IniciarSesionUseCase
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import kotlin.test.AfterTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

class AccesoRemotoCoder1Test {
    private val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
    private val db = EcolectaDatabase(driver.also { EcolectaDatabase.Schema.create(it) })
    private val reloj = RelojSistema()
    private val hasher = Pbkdf2PinHasher()
    private val usuarios = SqlDelightUsuarioRepository(db, Dispatchers.Unconfined)
    private val local = SqlDelightDatosServidorRepository(db, reloj, Dispatchers.Unconfined)
    private val auditoria = FakeAuditoriaRepository()
    private val cuenta = CuentaServidor(1, "coder1", "Cuenta ficticia Coder 1", "00000001", listOf("admin"))
    private val offline = LoginOfflineUseCase(usuarios, hasher, auditoria, reloj, object : DeviceIdProvider {
        override fun obtenerId() = "prueba-coder1"
    })

    @AfterTest
    fun cerrar() = driver.close()

    private suspend fun preparar(): String {
        val par = hasher.crearHash("1234")
        return local.guardarCuenta(cuenta, par.hash, par.salt, 1)
    }

    private fun ingreso(respuesta: Result<SesionServidor>, errorPersistencia: Exception? = null) = IniciarSesionUseCase(
        offline,
        object : ServidorWebRepository by ServidorWebNoConfigurado() {
            override val configurado = true
            override suspend fun autenticar(username: String, pin: String) = respuesta
            override suspend fun guardarSesion(usuarioId: String, sesion: SesionServidor) { errorPersistencia?.let { throw it } }
        }, local, usuarios, hasher, reloj,
    )

    @Test
    fun unRechazoRemotoNoSeConvierteEnIngresoOfflineAunqueElPinLocalCoincida() = runTest {
        val id = preparar()
        db.sesionQueries.establecer(id, "ADMIN")
        db.cambioPendienteQueries.marcar("usuario", id, id, null, 1)
        val resultado = ingreso(Result.failure(RechazoServidorException("Cuenta bloqueada", "cuenta_bloqueada")))("coder1", "1234")
        assertTrue(resultado.isFailure)
        assertTrue(assertNotNull(usuarios.obtenerPorId(id)).requiereVerificacion)
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
        assertNotNull(db.cambioPendienteQueries.porClave("usuario", id).executeAsOneOrNull())
        assertTrue(auditoria.insertados.isEmpty())
    }

    @Test
    fun sinRedPermiteUnPinLocalVigentePeroNoUnaRevocacionConocida() = runTest {
        val id = preparar()
        val sinRed = ingreso(Result.failure(SinConexionRemotaException()))
        assertTrue(sinRed("coder1", "1234").isSuccess)
        local.requerirVerificacion(id)
        assertIs<PinInvalidoException.RequiereConexion>(sinRed("coder1", "1234").exceptionOrNull())
        assertEquals(1, auditoria.insertados.size)
    }

    @Test
    fun validarElNuevoPinRestauraElAccesoYElAnteriorNoFuncionaOffline() = runTest {
        val id = preparar()
        local.requerirVerificacion(id)
        val valida = ingreso(Result.success(SesionServidor("token-ficticio", Long.MAX_VALUE, cuenta)))
        assertTrue(valida("coder1", "9876").isSuccess)
        assertFalse(assertNotNull(usuarios.obtenerPorId(id)).requiereVerificacion)
        assertTrue(offline("coder1", "9876").isSuccess)
        assertIs<PinInvalidoException.Incorrecto>(offline("coder1", "1234").exceptionOrNull())
    }

    @Test
    fun unPerfilServidorDesconocidoNoConcedeAccesoNiConvierteRoles() = runTest {
        preparar()
        val resultado = ingreso(Result.success(SesionServidor("token-ficticio", Long.MAX_VALUE, cuenta.copy(roles = listOf("asistente")))))("coder1", "1234")
        assertIs<PinInvalidoException.SinPerfilMovil>(resultado.exceptionOrNull())
    }

    @Test
    fun unErrorDelServidorNoSeConfundeConFaltaDeInternet() = runTest {
        val id = preparar()
        assertTrue(ingreso(Result.failure(IllegalStateException("Error de servicio de prueba")))("coder1", "1234").isFailure)
        assertFalse(assertNotNull(usuarios.obtenerPorId(id)).requiereVerificacion)
        assertTrue(auditoria.insertados.isEmpty())
    }

    @Test
    fun unFalloAlProtegerElTokenSeComunicaComoResultadoSinFingirLoginExitoso() = runTest {
        preparar()
        val fallo = pe.ecolecta.domain.security.SecretoLocalNoDisponibleException()
        val resultado = ingreso(Result.success(SesionServidor("token-ficticio", Long.MAX_VALUE, cuenta)), fallo)("coder1", "1234")
        assertIs<pe.ecolecta.domain.security.SecretoLocalNoDisponibleException>(resultado.exceptionOrNull())
        assertTrue(auditoria.insertados.isEmpty())
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
    }
    @Test
    fun pinAnteriorLargoConservaAccesoOfflineSinTransformarlo() = runTest {
        val par = hasher.crearHash("12345678")
        val id = local.guardarCuenta(cuenta, par.hash, par.salt, 1)
        assertTrue(offline("coder1", "12345678").isSuccess)
        assertEquals(par.hash, db.usuarioQueries.selectPorId(id).executeAsOne().pin_hash)
        assertTrue(offline("coder1", "1234").isFailure)
    }

}
