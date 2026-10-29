package pe.ecolecta.data

import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.runBlocking
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.local.DatabaseSeeder
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.repository.SqlDelightAuditoriaRepository
import pe.ecolecta.data.repository.SqlDelightDatosServidorRepository
import pe.ecolecta.data.repository.SqlDelightUsuarioRepository
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.ComunicadoServidor
import pe.ecolecta.domain.repository.AnalisisServidor
import pe.ecolecta.domain.repository.CuentaServidor
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.EntregaServidor
import pe.ecolecta.domain.repository.JornadaServidor
import pe.ecolecta.domain.repository.ProveedorServidor
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SesionServidor
import pe.ecolecta.domain.repository.VehiculoServidor
import pe.ecolecta.domain.repository.ZonaServidor
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.IniciarSesionUseCase
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Clock
import kotlin.time.Instant

/**
 * Panel web -> celular sobre la base SQLite real de la app (con sus datos de prueba): lo que cambia en la
 * web reemplaza la copia del celular sin duplicar filas ni pisar lo que el celular aún no envió.
 */
class DatosPanelWebTest {
    @Test fun `precios recibidos sobreviven a recrear el repositorio y vacio explicito retira tarifas`() = runBlocking {
        val datos = DatosServidor(
            servidorEn = 1000,
            zonas = listOf(ZonaServidor(999, "Zona precios")),
            proveedores = listOf(ProveedorServidor(999, "PRV-PRECIO", "Proveedor precio", "87654321", zonaId = 999)),
            tarifas = listOf(pe.ecolecta.domain.model.TarifaLeche(1, 1.805, "2026-10-01", "2026-10-31")),
        )
        val importador = SqlDelightDatosServidorRepository(db, reloj, kotlinx.coroutines.Dispatchers.IO)
        importador.aplicar(datos)
        val proveedor = db.proveedorQueries.selectTodos().executeAsList().first { it.codigo == "PRV-PRECIO" }.id
        val guardados = pe.ecolecta.data.repository.PortalProveedorRepository(db).precios(proveedor).first()
        assertEquals(1.805, guardados.vigente(LocalDate.parse("2026-10-26"))?.precio)
        assertEquals(1000L, guardados.recibidoEn)
        importador.aplicar(datos.copy(tarifas = null))
        assertEquals(guardados, pe.ecolecta.data.repository.PortalProveedorRepository(db).precios(proveedor).first())
        importador.aplicar(datos.copy(tarifas = emptyList()))
        assertTrue(pe.ecolecta.data.repository.PortalProveedorRepository(db).precios(proveedor).first().tarifas.isEmpty())
    }

    @Test
    fun `un solo login conecta la cuenta conocida y conserva el acceso offline sin guardar el PIN`() = runBlocking {
        val usuarios = SqlDelightUsuarioRepository(db, kotlinx.coroutines.Dispatchers.IO)
        val hasher = Pbkdf2PinHasher()
        var conectada = false
        var sinRed = false
        var rechazar = false
        var autenticaciones = 0
        val servidor = object : ServidorWebRepository {
            override val configurado = true
            override suspend fun vincular(usuarioId: String, username: String, pin: String) = error("El login no debe pedir un segundo enlace")
            override fun observarSesion(usuarioId: String): Flow<Boolean> = flowOf(conectada)
            override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor) = Result.success(Unit)
            override suspend fun liquidacionesDelProveedor(usuarioId: String) = Result.success(emptyList<PagoProveedor>())
            override suspend fun desvincular(usuarioId: String) = Unit
            override suspend fun autenticar(username: String, pin: String): Result<SesionServidor> {
                autenticaciones++
                return when {
                    sinRed -> Result.failure(pe.ecolecta.domain.repository.SinConexionRemotaException())
                    rechazar -> Result.failure(pe.ecolecta.domain.repository.RechazoServidorException("PIN incorrecto", "credenciales"))
                    else -> Result.success(SesionServidor("token-ficticio", Long.MAX_VALUE,
                        CuentaServidor(991, "acop_unico", "Acopiador prueba", "99009991", listOf("acopiador"))))
                }
            }
            override suspend fun guardarSesion(usuarioId: String, sesion: SesionServidor) { conectada = true }
        }
        val offline = LoginOfflineUseCase(usuarios, hasher, SqlDelightAuditoriaRepository(db, kotlinx.coroutines.Dispatchers.IO), reloj,
            object : DeviceIdProvider { override fun obtenerId() = "prueba" })
        val iniciar = IniciarSesionUseCase(offline, servidor, local, usuarios, hasher, reloj)
        val primera = iniciar("acop_unico", "1234").getOrThrow()
        assertTrue(primera.enlazado)
        assertEquals(primera.usuario.id, iniciar("acop_unico", "1234").getOrThrow().usuario.id)
        assertEquals(2, autenticaciones, "cada inicio online verifica que la cuenta siga habilitada sin pedir un enlace adicional")

        conectada = false
        assertTrue(iniciar("acop_unico", "1234").getOrThrow().enlazado, "renueva el acceso con el mismo login")
        conectada = false
        sinRed = true
        val sinConexion = iniciar("acop_unico", "1234").getOrThrow()
        assertTrue(!sinConexion.enlazado)
        assertEquals(primera.usuario.id, sinConexion.usuario.id)
        sinRed = false
        rechazar = true
        assertTrue(iniciar("acop_unico", "1234").isFailure, "un rechazo explícito del servidor no se oculta como falta de internet")
        assertNotNull(usuarios.obtenerPorId(primera.usuario.id), "no elimina la cuenta ni sus registros locales")
        Unit
    }

    private val reloj = object : Reloj {
        override fun hoy() = LocalDate(2026, 9, 24)
        override fun ahora(): Instant = Clock.System.now()
    }
    private val db = EcolectaDatabase(JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) })
        .also { DatabaseSeeder(it, reloj, Pbkdf2PinHasher()).sembrarSiEsNecesario() }
    private val local = SqlDelightDatosServidorRepository(db, reloj, kotlinx.coroutines.Dispatchers.IO)

    private val zonaFaon = db.zonaQueries.selectTodas().executeAsList().first { it.nombre == "FAON-MARKAPAJO" }
    private val acopFaon = db.usuarioQueries.selectPorUsername("acop_faon").executeAsOne()
    private val provFaon = db.proveedorQueries.selectPorCodigo("PRV-FAON-01").executeAsOne()

    private fun datos(
        zonaNombre: String = "FAON-MARKAPAJO",
        litros: Double = 40.0,
        anulada: Boolean = false,
        comunicados: List<ComunicadoServidor> = listOf(ComunicadoServidor(7, "Aviso", "Limpiar tachos", "Admin Web", 1_000)),
    ) = DatosServidor(
        completo = true,
        zonas = listOf(ZonaServidor(1, zonaNombre), ZonaServidor(2, "ZONA SOLO WEB")),
        vehiculos = listOf(VehiculoServidor(1, "Isuzu", "d4m-101")),
        usuarios = listOf(
            CuentaServidor(10, "acop_faon", "Juan Carlos Mamani Quispe", "99002001", listOf("acopiador")),
            CuentaServidor(11, "nuevo_web", "Rosa Apaza Huanca", "99005555", listOf("proveedor")),
        ),
        proveedores = listOf(
            ProveedorServidor(20, "PRV-FAON-01", "Nombre cambiado en la web", provFaon.dni, zonaId = 1),
            ProveedorServidor(21, "DEMO-PE-99", "Rosa Apaza Huanca", "99005555", zonaId = 2, tachos = 3, usuarioId = 11),
        ),
        jornadas = listOf(JornadaServidor(30, null, 10, 1, 1, "2026-09-23", 1_000, 2_000)),
        entregas = listOf(EntregaServidor(40, null, 30, 21, 10, 2, 1, litros, 1, null, 1_500, anulada)),
        analisis = listOf(
            AnalisisServidor(
                50, uuid = "an-web-50", proveedorId = 21, usuarioId = 10, codigoMuestra = "AN-WEB50", grasa = 3.5, aguaAnadida = 4.0,
                estado = "RECHAZADO", alertas = listOf("Agua añadida: 4.0 · Referencia: 0.0 %"),
                visita = kotlinx.serialization.json.buildJsonObject {
                    put("tecnicoNombre", kotlinx.serialization.json.JsonPrimitive("Rosa Apaza"))
                    put("parametrosAlertados", kotlinx.serialization.json.JsonArray(listOf(kotlinx.serialization.json.JsonPrimitive("agua"))))
                },
                registradoEn = 1_600,
            ),
        ),
        comunicados = comunicados,
    )

    @Test
    fun `lo que se cambia en la web aparece en el celular sin duplicar`() = runBlocking {
        local.aplicar(datos())
        local.aplicar(datos()) // repetir no duplica

        // Zonas: la del celular se reconoce por nombre; la nueva se crea; al renombrar en la web se sigue la misma fila.
        assertEquals(1, db.zonaQueries.selectTodas().executeAsList().count { it.nombre == "FAON-MARKAPAJO" })
        local.aplicar(datos(zonaNombre = "FAON RENOMBRADA"))
        assertEquals("FAON RENOMBRADA", db.zonaQueries.selectPorId(zonaFaon.id).executeAsOne().nombre)
        // Copia completa del admin: una zona que el panel ya no tiene queda inactiva.
        val otras = db.zonaQueries.selectTodas().executeAsList().filter { it.id != zonaFaon.id && it.nombre != "ZONA SOLO WEB" }
        assertTrue(otras.all { it.activo == 0L })

        // Vehículo nuevo con placa normalizada y proveedor existente actualizado.
        assertNotNull(db.vehiculoQueries.selectPorPlaca("D4M-101").executeAsOneOrNull())
        assertEquals("Nombre cambiado en la web", db.proveedorQueries.selectPorId(provFaon.id).executeAsOne().nombres)

        // Cuenta nueva de la web: existe con su rol, vinculada a su ficha, pero sin PIN local todavía.
        val nuevo = db.usuarioQueries.selectPorUsername("nuevo_web").executeAsOne()
        assertEquals("", nuevo.pin_hash)
        assertEquals(listOf(Rol.PROVEEDOR.name), db.usuarioRolQueries.selectPorUsuario(nuevo.id).executeAsList())
        assertEquals(nuevo.id, db.proveedorQueries.selectPorCodigo("DEMO-PE-99").executeAsOne().usuario_id)

        // Entrega registrada en la web, luego corregida y anulada allí.
        val entregaId = "web-entrega-40"
        assertEquals(40.0, db.entregaQueries.selectPorId(entregaId).executeAsOne().litros)
        local.aplicar(datos(litros = 35.5, anulada = true))
        val corregida = db.entregaQueries.selectPorId(entregaId).executeAsOne()
        assertEquals(35.5, corregida.litros)
        assertEquals(1L, corregida.anulada)
        assertEquals("SYNCED", corregida.sync_state)

        // Calidad y comunicados; un comunicado despublicado en la web desaparece del celular.
        val analisis = db.controlCalidadQueries.selectPorId("an-web-50").executeAsOne()
        assertEquals("RECHAZADO", analisis.estado)
        assertEquals(3.5, analisis.grasa)
        assertEquals("AN-WEB50", analisis.codigo_muestra)
        assertTrue("Rosa Apaza" in analisis.visita_json)
        assertEquals(1, db.comunicadoQueries.selectTodos().executeAsList().count { it.id == "web-comunicado-7" })
        local.aplicar(datos(comunicados = emptyList()))
        assertTrue(db.comunicadoQueries.selectTodos().executeAsList().none { it.id.startsWith("web-comunicado-") })
    }

    @Test
    fun `una entrega que el celular aun no envio no se pisa con la copia del panel`() = runBlocking {
        local.aplicar(datos())
        db.entregaQueries.corregir(litros = 50.0, tachos = 2, observaciones = "corregida aquí", updated_at = 9_999, id = "web-entrega-40")

        local.aplicar(datos(litros = 10.0))

        val entrega = db.entregaQueries.selectPorId("web-entrega-40").executeAsOne()
        assertEquals(50.0, entrega.litros)
        assertEquals("PENDING", entrega.sync_state)
    }

    @Test
    fun `una cuenta creada en la web entra con su PIN y queda guardada para entrar sin conexion`() = runBlocking {
        val usuarios = SqlDelightUsuarioRepository(db, kotlinx.coroutines.Dispatchers.IO)
        val hasher = Pbkdf2PinHasher()
        var tokenGuardado: String? = null
        val servidor = object : ServidorWebRepository {
            override val configurado = true
            override suspend fun vincular(usuarioId: String, username: String, pin: String) = Result.success(Unit)
            override fun observarSesion(usuarioId: String): Flow<Boolean> = flowOf(true)
            override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor) = Result.success(Unit)
            override suspend fun liquidacionesDelProveedor(usuarioId: String) = Result.success(emptyList<PagoProveedor>())
            override suspend fun desvincular(usuarioId: String) = Unit
            override suspend fun autenticar(username: String, pin: String): Result<SesionServidor> =
                if (username == "demo_calidad" && pin == "1234") {
                    Result.success(SesionServidor("tok", Long.MAX_VALUE, CuentaServidor(99, "demo_calidad", "Rosa Luz Apaza Huanca", "99001001", listOf("calidad", "produccion"))))
                } else {
                    Result.failure(IllegalStateException("credenciales"))
                }
            override suspend fun guardarSesion(usuarioId: String, sesion: SesionServidor) { tokenGuardado = usuarioId }
        }
        val dispositivo = object : DeviceIdProvider { override fun obtenerId() = "celular" }
        val offline = LoginOfflineUseCase(usuarios, hasher, SqlDelightAuditoriaRepository(db, kotlinx.coroutines.Dispatchers.IO), reloj, dispositivo)
        val iniciar = IniciarSesionUseCase(offline, servidor, local, usuarios, hasher, reloj)

        assertTrue(iniciar("demo_calidad", "9999").isFailure, "un PIN que el panel rechaza no entra")
        val inicio = iniciar("demo_calidad", "1234").getOrThrow()

        assertEquals(listOf(Rol.CALIDAD), inicio.usuario.roles)
        assertTrue(inicio.enlazado)
        assertEquals(inicio.usuario.id, tokenGuardado)
        // La próxima vez entra sin red con el mismo PIN.
        assertEquals(inicio.usuario.id, offline("demo_calidad", "1234").getOrThrow().id)
        assertNull(db.usuarioQueries.selectPorId(inicio.usuario.id).executeAsOne().bloqueado_hasta)
    }
    @Test
    fun `una correccion remota conserva la edicion offline y crea conflicto`() = kotlinx.coroutines.runBlocking {
        val inicial = datos()
        local.aplicar(inicial.copy(entregas = inicial.entregas.map { it.copy(actualizadoEn = 100) }))
        val id = "web-entrega-40"
        db.entregaQueries.corregir(litros = 38.0, tachos = 1, observaciones = "Cambio sin red", updated_at = 200, id = id)
        local.aplicar(inicial.copy(entregas = inicial.entregas.map { it.copy(litros = 35.0, actualizadoEn = 300) }))
        val fila = db.entregaQueries.selectPorId(id).executeAsOne()
        assertEquals(38.0, fila.litros)
        assertEquals(35.0, fila.litros_servidor)
        assertEquals("CONFLICT", fila.sync_state)
        assertEquals(300L, db.versionEntregaQueries.obtener(id).executeAsOne())
    }

    @Test
    fun `pagos y decisiones del panel se guardan sin volver a entrar en la cola`() = kotlinx.coroutines.runBlocking {
        val copia = datos().copy(
            pagos = listOf(pe.ecolecta.domain.repository.PagoServidor(99, 21, "2026-10-08", "2026-10-14", 20.0, 2.0, 40.0, 0.0, 40.0, "PAGADA")),
            solicitudes = listOf(pe.ecolecta.domain.repository.SolicitudServidor("recibida", 21, null, 20.0, "Revisión", creadaEn = 100, estado = "ATENDIDA")),
        )
        local.aplicar(copia)
        local.aplicar(copia)
        val json = kotlinx.serialization.json.Json
        assertEquals("PAGADA", json.decodeFromString<pe.ecolecta.domain.model.PagoProveedor>(db.portalProveedorQueries.porId("web-99").executeAsOne().contenido).estado)
        assertEquals("ATENDIDA", json.decodeFromString<pe.ecolecta.domain.model.SolicitudProveedor>(db.portalProveedorQueries.porId("recibida").executeAsOne().contenido).estado)
        assertEquals(0L, db.cambioPendienteQueries.contarPorEstado("PENDING").executeAsOne())
    }

    @Test
    fun `cierre administrativo actualiza jornada en memoria sin borrar entrega pendiente`() = runBlocking {
        val estado = pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository()
        val repositorio = SqlDelightDatosServidorRepository(db, reloj, kotlinx.coroutines.Dispatchers.Unconfined, estado)
        val inicial = datos().copy(jornadas = datos().jornadas.map { it.copy(cerradaEn = null) })
        repositorio.aplicar(inicial)
        val fila = db.jornadaQueries.selectPorId("web-jornada-30").executeAsOne()
        estado.establecer(pe.ecolecta.domain.model.Jornada(fila.id, fila.usuario_id, fila.zona_id, fila.vehiculo_id,
            LocalDate.parse(fila.fecha), fila.abierta_en, null, pe.ecolecta.domain.model.SyncState.SYNCED))
        db.cambioPendienteQueries.marcar("jornada", fila.id, fila.usuario_id, null, 250)
        db.entregaQueries.corregir(38.0, 1, "Sin internet", 300, "web-entrega-40")
        repositorio.aplicar(inicial.copy(jornadas = inicial.jornadas.map { it.copy(cerradaEn = 400, cierreAdministrativo = true) }))
        val actual = estado.observar().first()
        assertEquals(400L, actual?.cerradaEn)
        assertTrue(actual?.cierreAdministrativo == true)
        assertNull(db.cambioPendienteQueries.porClave("jornada", fila.id).executeAsOneOrNull())
        assertEquals(38.0, db.entregaQueries.selectPorId("web-entrega-40").executeAsOne().litros)
        assertEquals("PENDING", db.entregaQueries.selectPorId("web-entrega-40").executeAsOne().sync_state)
    }

    @Test
    fun `la lista de la zona sigue el orden de la ruta del panel y vuelve al alfabetico sin ruta`() = runBlocking {
        val base = datos()
        val conRuta = base.copy(proveedores = base.proveedores.map { if (it.id == 21L) it.copy(ordenRuta = 2) else it } + listOf(
            ProveedorServidor(22, "PRV-RUTA-01", "Zulma Choque", "99005556", zonaId = 2, ordenRuta = 1),
            ProveedorServidor(23, "PRV-RUTA-02", "Alberto Sin Ruta", "99005557", zonaId = 2),
        ))
        local.aplicar(conRuta)
        val zona = db.proveedorQueries.selectPorCodigo("DEMO-PE-99").executeAsOne().zona_id
        assertEquals(listOf("Zulma Choque", "Rosa Apaza Huanca", "Alberto Sin Ruta"),
            db.proveedorQueries.selectActivosPorZona(zona).executeAsList().map { it.nombres })

        local.aplicar(conRuta.copy(proveedores = conRuta.proveedores.map { it.copy(ordenRuta = null) }))
        assertEquals(listOf("Alberto Sin Ruta", "Rosa Apaza Huanca", "Zulma Choque"),
            db.proveedorQueries.selectActivosPorZona(zona).executeAsList().map { it.nombres })
    }

    @Test
    fun `el traslado aprobado guarda la fecha en que el panel cambia la zona`() = runBlocking {
        local.aplicar(datos().copy(solicitudes = listOf(pe.ecolecta.domain.repository.SolicitudServidor(
            "traslado-1", 21, null, null, "Traslado de zona", creadaEn = 100, estado = "APROBADA",
            tipo = "TRASLADO", zonaDestinoId = 1, fechaEfectiva = "2026-11-02",
        ))))
        val solicitud = kotlinx.serialization.json.Json.decodeFromString<pe.ecolecta.domain.model.SolicitudProveedor>(
            db.portalProveedorQueries.porId("traslado-1").executeAsOne().contenido)
        assertEquals("2026-11-02", solicitud.fechaEfectiva)
        assertNull(solicitud.aplicadoEn)
    }
}
