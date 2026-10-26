package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightCambiosLocalesRepository
import pe.ecolecta.domain.RelojSistema
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.ProveedorServidor
import pe.ecolecta.domain.repository.ZonaServidor
import kotlin.test.AfterTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlinx.serialization.json.jsonPrimitive
import kotlinx.serialization.json.boolean

class CatalogosDescargaCoder1Test {
    private val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY)
    private val db = EcolectaDatabase(driver.also { EcolectaDatabase.Schema.create(it) })
    private val local = SqlDelightDatosServidorRepository(db, RelojSistema(), Dispatchers.Unconfined)
    private val cuenta = CuentaServidor(10, "productor", "Responsable ficticio", "00000001", listOf("proveedor"))
    private val ficha = ProveedorServidor(20, "PRV-01", "Finca ficticia", "00000002", zonaId = 1, usuarioId = 10)
    private fun datos(proveedores: List<ProveedorServidor> = listOf(ficha)) = DatosServidor(
        completo = false, zonas = listOf(ZonaServidor(1, "ZONA DE PRUEBA")), vehiculos = emptyList(),
        usuarios = listOf(cuenta), proveedores = proveedores,
    )

    @AfterTest
    fun cerrar() = driver.close()

    @Test
    fun desvincularEnServidorLimpiaSoloLaFichaConfirmadaYRepetirNoDuplica() = runTest {
        local.aplicar(datos())
        local.aplicar(datos(listOf(ficha.copy(usuarioId = null))))
        local.aplicar(datos(listOf(ficha.copy(usuarioId = null))))
        assertNull(db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne().usuario_id)
        assertEquals(1, db.proveedorQueries.selectTodos().executeAsList().size)
        assertNotNull(db.usuarioQueries.selectPorUsername("productor").executeAsOneOrNull())
    }

    @Test
    fun unaAsignacionLocalPendienteNoSePierdePorUnNullRemoto() = runTest {
        local.aplicar(datos())
        val actual = db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne()
        db.cambioPendienteQueries.marcar("proveedor", actual.id, "admin-prueba", null, 1)
        local.aplicar(datos(listOf(ficha.copy(usuarioId = null, nombres = "Cambio remoto"))))
        assertEquals(actual, db.proveedorQueries.selectPorId(actual.id).executeAsOne())
        assertNotNull(db.cambioPendienteQueries.porClave("proveedor", actual.id).executeAsOneOrNull())
    }

    @Test
    fun reasignarNoDuplicaElVinculoNiAfectaLaFichaDeOtraCuenta() = runTest {
        local.aplicar(datos(listOf(ficha, ficha.copy(id = 21, codigo = "PRV-02", usuarioId = null))))
        local.aplicar(datos(listOf(ficha.copy(usuarioId = null), ficha.copy(id = 21, codigo = "PRV-02"))))
        assertNull(db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne().usuario_id)
        assertEquals("web-usuario-10", db.proveedorQueries.selectPorCodigo("PRV-02").executeAsOne().usuario_id)
    }

    @Test
    fun unaDescargaNoRobaLaCuentaDeOtraFichaPendiente() = runTest {
        local.aplicar(datos())
        val primera = db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne()
        db.cambioPendienteQueries.marcar("proveedor", primera.id, "admin-prueba", null, 1)
        local.aplicar(datos(listOf(ficha.copy(usuarioId = null), ficha.copy(id = 21, codigo = "PRV-02"))))
        assertEquals("web-usuario-10", db.proveedorQueries.selectPorId(primera.id).executeAsOne().usuario_id)
        assertNull(db.proveedorQueries.selectPorCodigo("PRV-02").executeAsOne().usuario_id)
    }

    @Test
    fun renombrarCodigoEnServidorConservaElIdLocalYSusDatos() = runTest {
        local.aplicar(datos())
        val original = db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne()
        local.aplicar(datos(listOf(ficha.copy(codigo = "PRV-RENOMBRADO"))))
        assertEquals(original.id, db.proveedorQueries.selectPorCodigo("PRV-RENOMBRADO").executeAsOne().id)
        assertNull(db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOneOrNull())
        assertEquals(1, db.proveedorQueries.selectTodos().executeAsList().size)
    }

    @Test
    fun asignacionRemotaExplicitaSeGuardaYUnServidorAnteriorNoLaBorra() = runTest {
        val copia = datos().copy(usuarios = listOf(cuenta.copy(roles = listOf("calidad"), zonaId = 1, asignacionZonaIncluida = true)))
        local.aplicar(copia)
        assertEquals("web-zona-1", db.usuarioZonaQueries.selectZonaId("web-usuario-10").executeAsOne())
        local.aplicar(datos())
        assertEquals("web-zona-1", db.usuarioZonaQueries.selectZonaId("web-usuario-10").executeAsOne())
        local.aplicar(copia.copy(usuarios = listOf(copia.usuarios.single().copy(zonaId = null))))
        assertNull(db.usuarioZonaQueries.selectZonaId("web-usuario-10").executeAsOneOrNull())
    }

    @Test
    fun asignacionPendienteSeConservaYSeIncluyeEnElEnvio() = runTest {
        local.aplicar(datos())
        val id = "web-usuario-10"
        db.usuarioZonaQueries.asignar(id, "web-zona-1")
        db.cambioPendienteQueries.marcar("usuario", id, "admin-prueba", null, 1)
        local.aplicar(datos().copy(usuarios = listOf(cuenta.copy(asignacionZonaIncluida = true))))
        assertEquals("web-zona-1", db.usuarioZonaQueries.selectZonaId(id).executeAsOne())
        val payload = assertNotNull(SqlDelightCambiosLocalesRepository(db, Dispatchers.Unconfined).pendientes().single().cuerpo)
        assertTrue(payload.getValue("asignacionZonaIncluida").jsonPrimitive.boolean)
        assertEquals("1", payload.getValue("zonaId").jsonPrimitive.content)
        assertEquals("ZONA DE PRUEBA", payload.getValue("zonaNombre").jsonPrimitive.content)
    }

    @Test
    fun descargarNoAdelantaLaRevisionBaseDeUnaEdicionPendiente() = runTest {
        local.aplicar(datos().copy(zonas = listOf(ZonaServidor(1, "BASE", revision = "a".repeat(64)))))
        val id = "web-zona-1"
        db.zonaQueries.actualizar("EDICION OFFLINE", 1, id)
        db.cambioPendienteQueries.marcar("zona", id, "admin-prueba", null, 1)
        local.aplicar(datos().copy(zonas = listOf(ZonaServidor(1, "EDICION WEB", revision = "b".repeat(64)))))
        assertEquals("EDICION OFFLINE", db.zonaQueries.selectPorId(id).executeAsOne().nombre)
        assertEquals("a".repeat(64), db.versionCatalogoQueries.obtener("zona", id).executeAsOne())
        val cuerpo = assertNotNull(SqlDelightCambiosLocalesRepository(db, Dispatchers.Unconfined).pendientes().single().cuerpo)
        assertTrue(cuerpo.getValue("controlVersion").jsonPrimitive.boolean)
        assertEquals("a".repeat(64), cuerpo.getValue("revisionBase").jsonPrimitive.content)
    }

    @Test
    fun recibirUnaRevocacionNoPisaElPerfilPendientePeroRetiraElAcceso() = runTest {
        local.aplicar(datos())
        val id = "web-usuario-10"
        db.cambioPendienteQueries.marcar("usuario", id, "admin-prueba", null, 1)
        db.sesionQueries.establecer(id, "PROVEEDOR")
        local.aplicar(datos().copy(usuarios = listOf(cuenta.copy(bloqueado = true))))
        assertEquals(1L, db.usuarioQueries.selectPorId(id).executeAsOne().requiere_verificacion)
        assertEquals(listOf("PROVEEDOR"), db.usuarioRolQueries.selectPorUsuario(id).executeAsList())
        assertNotNull(db.cambioPendienteQueries.porClave("usuario", id).executeAsOneOrNull())
        assertNull(db.sesionQueries.obtener().executeAsOneOrNull())
    }

    @Test
    fun responsableCompartidoAdmiteNullExplicitoSinQueUnServidorAntiguoLoBorre() = runTest {
        local.aplicar(datos(listOf(ficha.copy(dueno = "Responsable compartido", responsableIncluido = true))))
        assertEquals("Responsable compartido", db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne().dueno)
        local.aplicar(datos())
        assertEquals("Responsable compartido", db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne().dueno)
        local.aplicar(datos(listOf(ficha.copy(responsableIncluido = true))))
        assertNull(db.proveedorQueries.selectPorCodigo("PRV-01").executeAsOne().dueno)
    }
    @Test
    fun editarDocumentoLegadoSinCambiarloConservaCuentaYCola() = runTest {
        val legado = cuenta.copy(id = 30, username = "legado", dni = "LEGADO-123456", roles = listOf("acopiador"))
        val admin = cuenta.copy(id = 31, username = "admin", dni = "12345678", roles = listOf("admin"))
        local.aplicar(datos(emptyList()).copy(usuarios = listOf(legado, admin, legado.copy(id = 32, username = "otro"))))
        val id = "web-usuario-30"
        db.sesionQueries.establecer("web-usuario-31", "ADMIN")
        val usuarios = pe.ecolecta.data.repository.SqlDelightUsuarioRepository(db, Dispatchers.Unconfined)
        val zonas = pe.ecolecta.data.repository.SqlDelightZonaRepository(db, Dispatchers.Unconfined)
        val proveedores = pe.ecolecta.data.repository.SqlDelightProveedorRepository(db, Dispatchers.Unconfined)
        val jornadas = pe.ecolecta.data.repository.SqlDelightJornadaRepository(db, Dispatchers.Unconfined)
        val guardar = pe.ecolecta.domain.usecase.usuario.GuardarCuentaUseCase(
            usuarios, pe.ecolecta.data.repository.SqlDelightCuentasRepository(db, Dispatchers.Unconfined),
            pe.ecolecta.domain.usecase.usuario.ReglasCuenta(usuarios, zonas, proveedores, jornadas), proveedores,
            pe.ecolecta.data.repository.SqlDelightAuditoriaRepository(db, Dispatchers.Unconfined),
            pe.ecolecta.domain.security.Pbkdf2PinHasher(), RelojSistema(),
            object : pe.ecolecta.domain.DeviceIdProvider { override fun obtenerId() = "qa-legado" },
        )
        val datosCuenta = pe.ecolecta.domain.usecase.usuario.DatosCuenta(id, "legado", "Nombre ficticio corregido", checkNotNull(legado.dni),
            setOf(pe.ecolecta.domain.model.Rol.ACOPIADOR), true, null, null, null, null)
        assertTrue(guardar(datosCuenta, "web-usuario-31").isSuccess)
        assertEquals(legado.dni, db.usuarioQueries.selectPorId(id).executeAsOne().dni)
        assertEquals("Nombre ficticio corregido", db.usuarioQueries.selectPorId(id).executeAsOne().nombres)
        assertNotNull(db.cambioPendienteQueries.porClave("usuario", id).executeAsOneOrNull())
        assertTrue(guardar(datosCuenta.copy(dni = "OTRO-LEGADO"), "web-usuario-31").isFailure)
        assertEquals(legado.dni, db.usuarioQueries.selectPorId(id).executeAsOne().dni)
    }

    @Test fun ackDeCatalogoGuardaRevisionSinBorrarUnaEdicionPosterior() = runTest {
        local.aplicar(datos())
        val id = "web-usuario-10"
        val cola = SqlDelightCambiosLocalesRepository(db, Dispatchers.Unconfined, ProtectorSecretosPrueba)
        db.cambioPendienteQueries.marcar("usuario", id, id, null, 1)
        val enviado = cola.pendientes().single()
        db.cambioPendienteQueries.marcar("usuario", id, id, null, 2)
        cola.completarCatalogo(enviado, pe.ecolecta.domain.repository.ConfirmacionCatalogo(10, "revision-aceptada"))
        assertEquals("revision-aceptada", db.versionCatalogoQueries.obtener("usuario", id).executeAsOne())
        assertEquals(2L, db.cambioPendienteQueries.porClave("usuario", id).executeAsOne().marcado_en)
        assertEquals("revision-aceptada", cola.pendientes().single().cuerpo!!["revisionBase"]!!.jsonPrimitive.content)
        cola.completarCatalogo(cola.pendientes().single(), pe.ecolecta.domain.repository.ConfirmacionCatalogo(10, "revision-final"))
        assertNull(db.cambioPendienteQueries.porClave("usuario", id).executeAsOneOrNull())
        assertEquals("revision-final", db.versionCatalogoQueries.obtener("usuario", id).executeAsOne())
    }

}
