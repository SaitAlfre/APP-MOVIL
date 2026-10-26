package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.runBlocking
import kotlinx.datetime.LocalDate
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.jsonPrimitive
import pe.ecolecta.data.local.DatabaseSeeder
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightCambiosLocalesRepository
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.data.repository.SqlDelightZonaRepository
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.ZonaServidor
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.sync.EnviarCambiosServidorUseCase
import pe.ecolecta.domain.usecase.usuario.CambiarPinUsuarioUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Clock
import kotlin.time.Instant

/** Celular -> panel web sobre la base SQLite real de la app: la cola, el orden, los ids y los rechazos. */
class CambiosPanelWebTest {
    private val reloj = object : Reloj {
        override fun hoy() = LocalDate(2026, 9, 24)
        override fun ahora(): Instant = Clock.System.now()
    }
    private val db = EcolectaDatabase(JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) })
        .also { DatabaseSeeder(it, reloj, Pbkdf2PinHasher()).sembrarSiEsNecesario() }
    private val cola = SqlDelightCambiosLocalesRepository(db, Dispatchers.IO)
    private val admin = db.usuarioQueries.selectPorUsername("admin").executeAsOne()

    /** Panel falso: guarda lo recibido y responde un id nuevo, o el error indicado. */
    private inner class PanelFalso(var error: Exception? = null) : ServidorWebRepository {
        val recibidos = mutableListOf<Triple<String, String, JsonObject>>()
        private var siguienteId = 500L
        override val configurado = true
        override suspend fun vincular(usuarioId: String, username: String, pin: String) = Result.success(Unit)
        override fun observarSesion(usuarioId: String): Flow<Boolean> = flowOf(true)
        override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor) = Result.success(Unit)
        override suspend fun liquidacionesDelProveedor(usuarioId: String) = Result.success(emptyList<PagoProveedor>())
        override suspend fun desvincular(usuarioId: String) = Unit
        override suspend fun enviarCambio(usuarioId: String, entidad: String, cuerpo: JsonObject): Result<Long?> {
            error?.let { return Result.failure(it) }
            recibidos += Triple(usuarioId, entidad, cuerpo)
            return Result.success(siguienteId++)
        }
    }

    private fun iniciarSesionAdmin() = db.sesionQueries.establecer(admin.id, "ADMIN")

    @Test
    fun `un cambio con datos pero sin cuenta autora conserva la fila para reintentar`() = runBlocking {
        SqlDelightZonaRepository(db, Dispatchers.IO).insertar(Zona("zona-sin-autor", "SIN AUTOR", true))
        val panel = PanelFalso()

        val resultado = EnviarCambiosServidorUseCase(panel, cola)()

        assertEquals(0, resultado.enviados)
        assertTrue(panel.recibidos.isEmpty())
        val pendiente = db.cambioPendienteQueries.porClave("zona", "zona-sin-autor").executeAsOneOrNull()
        kotlin.test.assertNotNull(pendiente, "no se elimina un cambio que el panel no recibió")
        assertEquals("PENDING", pendiente.estado)
        assertTrue(pendiente.ultimo_error.orEmpty().contains("cuenta autora"))
    }

    @Test
    fun `editar con una cuenta valida permite enviar el cambio conservado sin autor`() = runBlocking {
        val zonas = SqlDelightZonaRepository(db, Dispatchers.IO)
        zonas.insertar(Zona("zona-sin-autor", "SIN AUTOR", true))
        val panel = PanelFalso()
        val enviar = EnviarCambiosServidorUseCase(panel, cola)
        enviar()

        iniciarSesionAdmin()
        zonas.actualizar(Zona("zona-sin-autor", "EDITADA POR ADMIN", true))
        assertEquals(1, enviar().enviados)
        assertEquals(admin.id, panel.recibidos.single().first)
        assertNull(db.cambioPendienteQueries.porClave("zona", "zona-sin-autor").executeAsOneOrNull())
    }

    @Test
    fun `una fila inexistente no se envia ni se cuenta como recibida por el panel`() = runBlocking {
        db.cambioPendienteQueries.marcar("zona", "fila-inexistente", admin.id, null, 1L)
        val panel = PanelFalso()

        assertEquals(0, EnviarCambiosServidorUseCase(panel, cola)().enviados)
        assertTrue(panel.recibidos.isEmpty())
        assertNull(db.cambioPendienteQueries.porClave("zona", "fila-inexistente").executeAsOneOrNull())
    }

    @Test
    fun `una zona creada en el celular llega al panel con la cuenta del admin y no se reenvia`() = runBlocking {
        iniciarSesionAdmin()
        SqlDelightZonaRepository(db, Dispatchers.IO).insertar(Zona("zona-nueva", "ZONA NUEVA", true))
        val panel = PanelFalso()
        val enviar = EnviarCambiosServidorUseCase(panel, cola)

        assertEquals(1, enviar().enviados)
        assertEquals(0, enviar().enviados, "ya enviada: no se repite")

        val (autor, entidad, cuerpo) = panel.recibidos.single()
        assertEquals(admin.id, autor)
        assertEquals("zona", entidad)
        assertEquals("ZONA NUEVA", cuerpo["nombre"]!!.jsonPrimitive.content)
        assertEquals(500L, db.servidorMapaQueries.servidorId("zona", "zona-nueva").executeAsOne())

        // Un segundo cambio viaja con el id del panel: allí se actualiza la misma zona.
        SqlDelightZonaRepository(db, Dispatchers.IO).actualizar(Zona("zona-nueva", "ZONA RENOMBRADA", true))
        enviar()
        assertEquals("500", panel.recibidos.last().third["servidorId"]!!.jsonPrimitive.content)
    }

    @Test
    fun `el PIN nuevo viaja una sola vez y luego se borra del celular`() = runBlocking {
        iniciarSesionAdmin()
        val acopiador = db.usuarioQueries.selectPorUsername("acop_faon").executeAsOne()
        CambiarPinUsuarioUseCase(SqlDelightUsuarioRepository(db, Dispatchers.IO), Pbkdf2PinHasher(), reloj, cola)(acopiador.id, "4321", "4321")
        val panel = PanelFalso()

        EnviarCambiosServidorUseCase(panel, cola)()

        assertEquals("4321", panel.recibidos.single { it.second == "usuario" }.third["pin"]!!.jsonPrimitive.content)
        assertNull(db.cambioPendienteQueries.porClave("usuario", acopiador.id).executeAsOneOrNull())
    }

    @Test
    fun `un rechazo por el dato queda en error y uno sin red queda pendiente`() = runBlocking {
        iniciarSesionAdmin()
        SqlDelightZonaRepository(db, Dispatchers.IO).insertar(Zona("zona-x", "ZONA X", true))

        EnviarCambiosServidorUseCase(PanelFalso(SinConexionRemotaException(RuntimeException(), "sin red")), cola)()
        assertEquals("PENDING", db.cambioPendienteQueries.porClave("zona", "zona-x").executeAsOne().estado)

        val rechazo = PanelFalso(RechazoServidorException("Ya existe una zona con ese nombre.", "invalido"))
        EnviarCambiosServidorUseCase(rechazo, cola)()
        val fila = db.cambioPendienteQueries.porClave("zona", "zona-x").executeAsOne()
        assertEquals("ERROR", fila.estado)
        assertEquals("Ya existe una zona con ese nombre.", fila.ultimo_error)
        assertTrue(rechazo.recibidos.isEmpty())
    }

    @Test
    fun `lo que llega del panel no pisa un cambio del celular aun sin enviar`() = runBlocking {
        iniciarSesionAdmin()
        val faon = db.zonaQueries.selectPorNombre("FAON-MARKAPAJO").executeAsOne()
        SqlDelightZonaRepository(db, Dispatchers.IO).actualizar(Zona(faon.id, "FAON CAMBIADA EN EL CELULAR", true))
        db.servidorMapaQueries.guardar("zona", 1, faon.id)

        SqlDelightDatosServidorRepository(db, reloj, Dispatchers.IO).aplicar(DatosServidor(zonas = listOf(ZonaServidor(1, "FAON-MARKAPAJO"))))

        assertEquals("FAON CAMBIADA EN EL CELULAR", db.zonaQueries.selectPorId(faon.id).executeAsOne().nombre)
    }

    @Test
    fun `un cambio rechazado no se pierde al descargar la copia del panel`() = runBlocking {
        iniciarSesionAdmin()
        val faon = db.zonaQueries.selectPorNombre("FAON-MARKAPAJO").executeAsOne()
        db.servidorMapaQueries.guardar("zona", 1, faon.id)
        SqlDelightZonaRepository(db, Dispatchers.IO).actualizar(Zona(faon.id, "FAON CAMBIADA EN EL CELULAR", true))
        val panel = PanelFalso(RechazoServidorException("Revisa el nombre del sector.", "invalido"))
        assertEquals(1, EnviarCambiosServidorUseCase(panel, cola)().conError)

        SqlDelightDatosServidorRepository(db, reloj, Dispatchers.IO).aplicar(
            DatosServidor(zonas = listOf(ZonaServidor(1, "FAON-MARKAPAJO"))),
        )

        assertEquals("FAON CAMBIADA EN EL CELULAR", db.zonaQueries.selectPorId(faon.id).executeAsOne().nombre)
        val pendiente = db.cambioPendienteQueries.porClave("zona", faon.id).executeAsOne()
        assertEquals("ERROR", pendiente.estado)
        assertEquals("Revisa el nombre del sector.", pendiente.ultimo_error)
    }

    @Test
    fun `una descarga completa no desactiva una zona cuyo cambio esta rechazado`() = runBlocking {
        iniciarSesionAdmin()
        val faon = db.zonaQueries.selectPorNombre("FAON-MARKAPAJO").executeAsOne()
        db.servidorMapaQueries.guardar("zona", 1, faon.id)
        SqlDelightZonaRepository(db, Dispatchers.IO).actualizar(Zona(faon.id, "FAON SIN CONFIRMAR", true))
        EnviarCambiosServidorUseCase(PanelFalso(RechazoServidorException("Nombre no aceptado.", "invalido")), cola)()

        SqlDelightDatosServidorRepository(db, reloj, Dispatchers.IO).aplicar(DatosServidor(completo = true))

        val zona = db.zonaQueries.selectPorId(faon.id).executeAsOne()
        assertEquals("FAON SIN CONFIRMAR", zona.nombre)
        assertEquals(1L, zona.activo)
        assertEquals("ERROR", db.cambioPendienteQueries.porClave("zona", faon.id).executeAsOne().estado)
    }

    @Test
    fun `un analisis de calidad del celular viaja completo al panel`() = runBlocking {
        val tecnico = db.usuarioQueries.selectPorUsername("calidad_faon").executeAsOne()
        db.sesionQueries.establecer(tecnico.id, "CALIDAD")
        val proveedor = db.proveedorQueries.selectPorCodigo("PRV-FAON-01").executeAsOne()
        val control = pe.ecolecta.domain.model.ControlCalidad(
            id = "an-local-1", proveedorId = proveedor.id, usuarioId = tecnico.id, codigoMuestra = "AN-LOCAL1", loteRecipiente = null,
            volumenL = null, origenCaptura = pe.ecolecta.domain.model.OrigenCaptura.ESCANER, serialAnalizador = "LS-9", modoAnalizador = null,
            temperatura = 6.0, grasa = 2.1, sng = 8.7, densidad = null, proteina = null, lactosa = null, sales = null, solidosTotales = null,
            aguaAnadida = 0.0, puntoCongelacion = -0.53, ph = null, apariencia = null, observaciones = "Revisar",
            estado = pe.ecolecta.domain.model.EstadoControlCalidad.OBSERVADO, alertas = listOf("Grasa: 2.1 · Referencia: 3.0 a 6.0 %"),
            textoComprobante = null, registradoEn = 1_000, updatedAt = 1_000, syncState = pe.ecolecta.domain.model.SyncState.PENDING,
            visita = pe.ecolecta.domain.model.DatosVisitaCalidad(tecnicoNombre = "Miguel Vargas", parametrosAlertados = listOf("grasa")),
        )
        pe.ecolecta.data.repository.SqlDelightControlCalidadRepository(db, Dispatchers.IO).insertar(control)
        val panel = PanelFalso()

        EnviarCambiosServidorUseCase(panel, cola)()

        val (autor, entidad, cuerpo) = panel.recibidos.single { it.second == "calidad" }
        assertEquals(tecnico.id, autor)
        assertEquals("calidad", entidad)
        assertEquals("an-local-1", cuerpo["uuid"]!!.jsonPrimitive.content)
        assertEquals("2.1", cuerpo["grasa"]!!.jsonPrimitive.content)
        assertEquals("-0.53", cuerpo["puntoCongelacion"]!!.jsonPrimitive.content)
        assertEquals("OBSERVADO", cuerpo["estado"]!!.jsonPrimitive.content)
        assertEquals("Miguel Vargas", (cuerpo["visita"] as JsonObject)["tecnicoNombre"]!!.jsonPrimitive.content)
    }
    @Test
    fun `reclamo y traslado usan las rutas reales y solo salen al confirmar`() = runBlocking {
        iniciarSesionAdmin()
        val proveedor = db.proveedorQueries.selectTodos().executeAsList().first()
        val portal = pe.ecolecta.data.repository.PortalProveedorRepository(db)
        val json = kotlinx.serialization.json.Json
        portal.guardar(pe.ecolecta.domain.model.SolicitudProveedor("r-sync", proveedor.id, "RECLAMO", "entrega-1", 25.0,
            "Litros mal registrados", "Revisar los litros anotados", creadaEn = 100))
        portal.guardar(pe.ecolecta.domain.model.SolicitudProveedor("t-sync", proveedor.id, "TRASLADO", proveedor.zona_id, null,
            "Traslado de zona", "Cambio de ruta de prueba", creadaEn = 101))
        val panel = PanelFalso()
        EnviarCambiosServidorUseCase(panel, cola)(admin.id)
        assertEquals(setOf("reclamo", "traslado"), panel.recibidos.map { it.second }.toSet())
        assertTrue(cola.pendientes().isEmpty())
        val guardada = db.portalProveedorQueries.porId("t-sync").executeAsOne()
        assertEquals("PENDIENTE", json.decodeFromString<pe.ecolecta.domain.model.SolicitudProveedor>(guardada.contenido).estado)
    }

    @Test
    fun `sin conexion una solicitud se conserva y no se marca recibida`() = runBlocking {
        iniciarSesionAdmin()
        val proveedor = db.proveedorQueries.selectTodos().executeAsList().first()
        pe.ecolecta.data.repository.PortalProveedorRepository(db).guardar(pe.ecolecta.domain.model.SolicitudProveedor(
            "sin-red", proveedor.id, "RECLAMO", "entrega-1", 25.0, "Otro motivo", "Revisar anotaciones", creadaEn = 100))
        EnviarCambiosServidorUseCase(PanelFalso(SinConexionRemotaException()), cola)(admin.id)
        assertEquals(1, cola.pendientes().size)
        assertEquals("PENDIENTE_ENVIO", kotlinx.serialization.json.Json.decodeFromString<pe.ecolecta.domain.model.SolicitudProveedor>(db.portalProveedorQueries.porId("sin-red").executeAsOne().contenido).estado)
    }

    @Test
    fun `una cuenta no envia la cola de otra`() = runBlocking {
        iniciarSesionAdmin()
        SqlDelightZonaRepository(db, Dispatchers.IO).insertar(Zona("solo-admin", "Solo admin", true))
        val panel = PanelFalso()
        EnviarCambiosServidorUseCase(panel, cola)("otra-cuenta")
        assertTrue(panel.recibidos.isEmpty())
        assertEquals(1, cola.pendientes().size)
    }

    @Test
    fun `recibir pagos del panel no crea operaciones de escritura`() = runBlocking {
        iniciarSesionAdmin()
        val proveedor = db.proveedorQueries.selectTodos().executeAsList().first()
        val pago = PagoProveedor("web-99", proveedor.id, "2026-10-08", "2026-10-14", 20.0, 2.0, 40.0, 0.0, 40.0, "PAGADA")
        pe.ecolecta.data.repository.SqlDelightGestionPortalRepository(db, Dispatchers.IO).recibirPagos(listOf(pago), 100)
        assertTrue(cola.pendientes().isEmpty())
        assertTrue(db.portalProveedorQueries.porId(pago.id).executeAsOne().contenido.contains("PAGADA"))
    }

}
