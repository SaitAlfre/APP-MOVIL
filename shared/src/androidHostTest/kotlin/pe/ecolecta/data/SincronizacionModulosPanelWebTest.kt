package pe.ecolecta.data

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking
import kotlinx.serialization.json.JsonNull
import kotlinx.serialization.json.JsonObject
import kotlinx.serialization.json.add
import kotlinx.serialization.json.buildJsonArray
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put
import org.junit.Assume.assumeTrue
import org.koin.dsl.koinApplication
import org.koin.dsl.module
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.di.dataModule
import pe.ecolecta.di.domainModule
import pe.ecolecta.domain.model.ControlCalidad
import pe.ecolecta.domain.model.DatosVisitaCalidad
import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.OrigenCaptura
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.domain.repository.DatosServidor
import pe.ecolecta.domain.repository.DatosServidorLocalRepository
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.sync.CoordinadorSync
import pe.ecolecta.domain.usecase.sync.EnviarCambiosServidorUseCase
import pe.ecolecta.domain.usecase.admin.fechaLima
import java.net.CookieManager
import java.net.URI
import java.net.URLEncoder
import java.net.http.HttpClient
import java.net.http.HttpRequest
import java.net.http.HttpResponse
import java.sql.DriverManager
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertTrue

/**
 * Recorrido real WEB ↔ APP sobre un Laravel aislado (BD desechable con MovilDemoSeeder): catálogos, rutas,
 * calidad, permisos por rol, retiro/suspensión de fichas y acopio sin conexión. Los cambios del panel se hacen
 * con sus formularios (sesión y CSRF reales) y los del celular con los repositorios y la cola de la app.
 */
class SincronizacionModulosPanelWebTest {
    private class Panel(private val url: String) {
        private val http = HttpClient.newBuilder().cookieHandler(CookieManager()).build()
        private var csrf = ""
        fun abrir(path: String): String {
            val respuesta = http.send(HttpRequest.newBuilder(URI.create(url + path)).GET().build(), HttpResponse.BodyHandlers.ofString())
            assertEquals(200, respuesta.statusCode(), path)
            csrf = Regex("name=\"_token\" value=\"([^\"]+)\"").find(respuesta.body())?.groupValues?.get(1) ?: csrf
            return respuesta.body()
        }
        fun enviar(path: String, datos: Map<String, String>): HttpResponse<String> {
            val cuerpo = (datos + ("_token" to csrf)).entries.joinToString("&") {
                URLEncoder.encode(it.key, "UTF-8") + "=" + URLEncoder.encode(it.value, "UTF-8")
            }
            val respuesta = http.send(HttpRequest.newBuilder(URI.create(url + path))
                .header("Content-Type", "application/x-www-form-urlencoded").header("Referer", "$url/admin")
                .POST(HttpRequest.BodyPublishers.ofString(cuerpo)).build(), HttpResponse.BodyHandlers.ofString())
            assertEquals(302, respuesta.statusCode(), "$path → ${respuesta.headers().firstValue("location").orElse("")}")
            assertFalse(respuesta.headers().firstValue("location").orElse("").endsWith("/login"), path)
            return respuesta
        }
    }

    /** Corta la red del celular sin tocar el servidor. */
    private class Canal(private val real: ServidorWebRepository) : ServidorWebRepository by real {
        var conectado = true
        override suspend fun descargarDatos(usuarioId: String): Result<DatosServidor> =
            if (conectado) real.descargarDatos(usuarioId) else Result.failure(SinConexionRemotaException())
        override suspend fun enviarCambio(usuarioId: String, entidad: String, cuerpo: JsonObject): Result<Long?> =
            if (conectado) real.enviarCambio(usuarioId, entidad, cuerpo) else Result.failure(SinConexionRemotaException())
        override suspend fun enviarCatalogo(usuarioId: String, entidad: String, cuerpo: JsonObject) =
            if (conectado) real.enviarCatalogo(usuarioId, entidad, cuerpo) else Result.failure(SinConexionRemotaException())
        override suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor): Result<Unit> =
            if (conectado) real.enviarEntrega(usuarioId, entrega) else Result.failure(SinConexionRemotaException())
    }

    @Test
    fun `catalogos rutas calidad permisos estados y acopio offline viajan en ambos sentidos`() = runBlocking<Unit> {
        val url = System.getenv("ECOLECTA_SERVIDOR_PRUEBA").orEmpty()
        val base = System.getenv("ECOLECTA_SERVIDOR_BD").orEmpty()
        val pinAdmin = System.getenv("ECOLECTA_PIN_ADMIN").orEmpty()
        assumeTrue("Solo backend aislado en localhost", url == "http://127.0.0.1:8765" && base.isNotBlank() && pinAdmin.isNotBlank())
        fun consultar(sql: String, vararg parametros: Any): List<List<Any?>> = DriverManager.getConnection("jdbc:sqlite:$base").use { c ->
            c.prepareStatement(sql).use { q ->
                parametros.forEachIndexed { i, p -> q.setObject(i + 1, p) }
                q.executeQuery().use { rs -> buildList { while (rs.next()) add((1..rs.metaData.columnCount).map { rs.getObject(it) }) } }
            }
        }
        fun idWeb(tabla: String, columna: String, valor: String): Long =
            (consultar("SELECT id FROM $tabla WHERE $columna = ?", valor).single()[0] as Number).toLong()

        val driver = JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) }
        val app = koinApplication {
            allowOverride(true)
            modules(dataModule, domainModule, module {
                single<SqlDriver> { driver }
                single<pe.ecolecta.domain.DeviceIdProvider> { object : pe.ecolecta.domain.DeviceIdProvider { override fun obtenerId() = "modulos-qa" } }
                single<pe.ecolecta.domain.security.ProtectorSecretos> { ProtectorSecretosPrueba }
                single<ServidorWebRepository> { Canal(ServidorWebRepositoryKtor(url, get(), get(), get(), get(), Dispatchers.IO, protector = get())) }
                single<RegistroAcopioRemotoRepository> { pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente() }
            })
        }
        try {
            val k = app.koin
            val servidor = k.get<ServidorWebRepository>() as Canal
            val db = k.get<EcolectaDatabase>()
            val local = k.get<DatosServidorLocalRepository>()
            suspend fun entrar(username: String, pin: String, rol: Rol): String {
                val remota = servidor.autenticar(username, pin).getOrThrow()
                val hash = Pbkdf2PinHasher().crearHash(pin)
                val id = local.guardarCuenta(remota.cuenta!!, hash.hash, hash.salt, 1)
                servidor.guardarSesion(id, remota)
                k.get<SesionRepository>().iniciar(Sesion(k.get<UsuarioRepository>().obtenerPorId(id)!!, rol))
                k.get<CoordinadorSync>().ejecutar(manual = true)
                return id
            }
            suspend fun sincronizar() = k.get<CoordinadorSync>().ejecutar(manual = true)
            val sufijo = java.util.UUID.randomUUID().toString().take(6).lowercase()
            val dni = { n: Int -> ((System.nanoTime() + n).mod(90_000_000L) + 10_000_000L).toString() }
            val panel = Panel(url)
            panel.abrir("/login")
            panel.enviar("/login", mapOf("username" to "admin", "pin" to pinAdmin))

            // 1) APP → WEB: el administrador crea en el celular una zona, dos fichas y una cuenta de Calidad.
            val admin = entrar("admin", pinAdmin, Rol.ADMIN)
            val zonaNombre = "QA-ZONA-$sufijo".uppercase()
            servidor.enviarCambio(admin, "zona", buildJsonObject { put("nombre", zonaNombre); put("activo", true) }).getOrThrow()
            val fichas = listOf("QA-A-$sufijo" to "Ana Ruta $sufijo", "QA-B-$sufijo" to "Beto Ruta $sufijo").mapIndexed { i, (codigo, nombre) ->
                servidor.enviarCambio(admin, "proveedor", buildJsonObject {
                    put("codigo", codigo); put("nombres", nombre); put("dni", dni(i)); put("telefono", JsonNull); put("direccion", JsonNull)
                    put("zonaNombre", zonaNombre); put("tachos", 2); put("capacidadTachoL", 40.0); put("estado", "ACTIVO"); put("usuarioUsername", JsonNull)
                }).getOrThrow()
                codigo
            }
            val calidadUser = "qa_cal_$sufijo"
            servidor.enviarCambio(admin, "usuario", buildJsonObject {
                put("username", calidadUser); put("nombres", "Calidad QA $sufijo"); put("dni", dni(7)); put("activo", true); put("pin", "1357")
                put("roles", buildJsonArray { add("calidad") }); put("asignacionZonaIncluida", true); put("zonaNombre", zonaNombre)
            }).getOrThrow()
            val zonaWeb = idWeb("zonas", "nombre", zonaNombre)
            val (provA, provB) = fichas.map { idWeb("proveedores", "codigo", it) }
            assertEquals(zonaWeb, (consultar("SELECT zona_id FROM usuarios WHERE username = ?", calidadUser).single()[0] as Number).toLong())
            assertTrue(panel.abrir("/admin/proveedores?buscar=QA-A-$sufijo").contains("Ana Ruta $sufijo"))

            // 2) WEB → APP: el panel renombra una ficha y dibuja una ruta B → A con el acopiador asignado.
            panel.abrir("/admin/proveedores/$provA/editar")
            panel.enviar("/admin/proveedores/$provA", mapOf("_method" to "PUT", "codigo" to fichas[0], "nombres" to "Ana Editada Web $sufijo",
                "dni" to consultar("SELECT dni FROM proveedores WHERE id = ?", provA).single()[0].toString(), "zona_id" to "$zonaWeb",
                "tachos" to "3", "capacidad_tacho_l" to "40"))
            val acopiadorWeb = idWeb("usuarios", "username", "acop_faon")
            val parada = { id: Long, n: String, lat: Double -> """{"nombre":"$n","proveedor_id":$id,"latitud":$lat,"longitud":-69.97,"referencia":""}""" }
            panel.abrir("/admin/mapa-rutas/nueva")
            panel.enviar("/admin/mapa-rutas", mapOf("nombre" to "Ruta QA $sufijo", "zona_id" to "$zonaWeb", "activo" to "1",
                "acopiador_id" to "$acopiadorWeb", "tipo_vehiculo" to "camion",
                "paradas_json" to "[${parada(provB, "B", -15.60)},${parada(provA, "A", -15.61)}]", "trazado_json" to "[]"))
            val acopiador = entrar("acop_faon", "2468", Rol.ACOPIADOR)
            val zonaLocal = k.get<ZonaRepository>().observarTodas().first().single { it.nombre == zonaNombre }
            val lista = db.proveedorQueries.selectActivosPorZona(zonaLocal.id).executeAsList()
            assertEquals(listOf(fichas[1], fichas[0]), lista.map { it.codigo }, "La lista del acopiador sigue la ruta del panel")
            assertEquals("Ana Editada Web $sufijo", lista.last().nombres)
            assertEquals(3L, lista.last().tachos)

            // 3) PERMISOS: cada rol solo escribe lo suyo, aunque la app fallara en ocultarlo.
            val sinPermiso = servidor.enviarCambio(acopiador, "proveedor", buildJsonObject {
                put("codigo", fichas[0]); put("nombres", "Intruso"); put("dni", dni(1)); put("zonaNombre", zonaNombre); put("estado", "ACTIVO"); put("usuarioUsername", JsonNull)
            }).exceptionOrNull()
            assertIs<RechazoServidorException>(sinPermiso)
            assertEquals("Ana Editada Web $sufijo", consultar("SELECT nombres FROM proveedores WHERE id = ?", provA).single()[0])

            // 4) ACOPIO OFFLINE: se registra sin red, se reintenta y llega una sola vez.
            val vehiculo = k.get<VehiculoRepository>().observarTodos().first().first { it.placa == "V1A-123" }
            val jornada = k.get<pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase>()(acopiador, zonaLocal.id, vehiculo.id).getOrThrow()
            servidor.conectado = false
            val entregas = listOf(lista[0], lista[1]).map { p ->
                k.get<pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase>()(jornada.id, p.id, acopiador, zonaLocal.id, vehiculo.id, 15.5, 1, null)
                    .getOrThrow().entrega
            }
            sincronizar()
            entregas.forEach { assertEquals(SyncState.PENDING, k.get<EntregaRepository>().obtenerPorId(it.id)!!.syncState) }
            assertTrue(consultar("SELECT id FROM entregas WHERE uuid_movil = ?", entregas[0].id).isEmpty())
            servidor.conectado = true
            sincronizar()
            sincronizar()
            entregas.forEach { e ->
                assertEquals(SyncState.SYNCED, k.get<EntregaRepository>().obtenerPorId(e.id)!!.syncState)
                assertEquals(1, consultar("SELECT id FROM entregas WHERE uuid_movil = ?", e.id).size, "Sin duplicados")
            }
            assertTrue(panel.abrir("/admin/entregas").contains("Beto Ruta $sufijo"))

            // 5) RETIRO EN LA WEB: la app deja de ofrecer la ficha y el panel rechaza una entrega offline posterior.
            servidor.conectado = false
            val tardia = k.get<pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase>()(jornada.id, lista[0].id, acopiador, zonaLocal.id, vehiculo.id, 9.0, 1, null)
                .getOrThrow().entrega
            panel.abrir("/admin/proveedores")
            panel.enviar("/admin/proveedores/$provB/estado", mapOf("_method" to "PATCH", "estado" to "retirado", "motivo_estado" to "Prueba integrada"))
            servidor.conectado = true
            sincronizar()
            assertEquals(listOf(fichas[0]), db.proveedorQueries.selectActivosPorZona(zonaLocal.id).executeAsList().map { it.codigo })
            assertEquals("RETIRADO", db.proveedorQueries.selectPorCodigo(fichas[1]).executeAsOne().estado)
            val rechazada = k.get<EntregaRepository>().obtenerPorId(tardia.id)!!
            assertTrue(rechazada.syncState == SyncState.ERROR || rechazada.syncState == SyncState.CONFLICT, "Estado: ${rechazada.syncState}")
            assertTrue(consultar("SELECT id FROM entregas WHERE uuid_movil = ?", tardia.id).isEmpty())
            assertEquals(1, consultar("SELECT id FROM entregas WHERE uuid_movil = ?", entregas[0].id).size, "Lo anterior se conserva")

            // 6) CALIDAD APP → WEB: el análisis hecho offline sube una sola vez y aparece en el panel.
            val calidad = entrar(calidadUser, "1357", Rol.CALIDAD)
            val fichaA = db.proveedorQueries.selectPorCodigo(fichas[0]).executeAsOne()
            val ahora = System.currentTimeMillis()
            val analisisId = "analisis-qa-$sufijo"
            servidor.conectado = false
            k.get<ControlCalidadRepository>().insertar(ControlCalidad(
                id = analisisId, proveedorId = fichaA.id, usuarioId = calidad, codigoMuestra = "AN-QA-$sufijo".uppercase(), loteRecipiente = null,
                volumenL = null, origenCaptura = OrigenCaptura.MANUAL, serialAnalizador = null, modoAnalizador = null, temperatura = 6.0,
                grasa = 3.6, sng = 8.4, densidad = 1.030, proteina = 3.1, lactosa = 4.6, sales = 0.7, solidosTotales = 12.0, aguaAnadida = 0.0,
                puntoCongelacion = -0.53, ph = 6.7, apariencia = null, observaciones = "Prueba integrada", estado = EstadoControlCalidad.APROBADO,
                alertas = emptyList(), textoComprobante = null, registradoEn = ahora, updatedAt = ahora, syncState = SyncState.PENDING,
                visita = DatosVisitaCalidad(proveedorNombre = fichaA.nombres, proveedorCodigo = fichaA.codigo, zonaId = zonaLocal.id, zonaNombre = zonaNombre),
            ))
            val envio = k.get<EnviarCambiosServidorUseCase>()
            assertTrue(envio(calidad).sinConexion)
            servidor.conectado = true
            assertEquals(1, envio(calidad).enviados)
            assertEquals(0, envio(calidad).enviados)
            assertEquals(1, consultar("SELECT id FROM analisis_calidad WHERE uuid = ?", analisisId).size)
            assertTrue(panel.abrir("/admin/calidad/$analisisId").contains(fichaA.nombres))

            // 7) CALIDAD WEB → APP: un análisis registrado en el panel llega a la cuenta de Calidad.
            panel.abrir("/admin/calidad/nuevo")
            panel.enviar("/admin/calidad", mapOf("proveedor_id" to "$provA", "fecha" to fechaLima(ahora).toString(), "hora" to "08:30",
                "unidad_congelacion" to "°C", "valores[grasa]" to "3.4", "valores[sng]" to "8.5", "valores[densidad]" to "1.031", "valores[ph]" to "6.7"))
            sincronizar()
            val recibidos = k.get<ControlCalidadRepository>().observarTodos().first().filter { it.proveedorId == fichaA.id }
            assertEquals(2, recibidos.size, "El análisis de la web y el del celular, sin duplicar")
            assertTrue(recibidos.any { it.grasa == 3.4 })

            // 8) SUSPENSIÓN DE FICHA: su cuenta conserva consulta, pero el panel rechaza nuevas solicitudes.
            val provUser = "qa_prov_$sufijo"
            servidor.enviarCambio(admin, "usuario", buildJsonObject {
                put("username", provUser); put("nombres", "Ana Editada Web $sufijo")
                put("dni", consultar("SELECT dni FROM proveedores WHERE id = ?", provA).single()[0].toString()); put("activo", true); put("pin", "2468")
                put("roles", buildJsonArray { add("proveedor") })
            }).getOrThrow()
            panel.abrir("/admin/proveedores/$provA")
            panel.enviar("/admin/proveedores/$provA/vincular", mapOf("usuario_id" to idWeb("usuarios", "username", provUser).toString()))
            assertEquals(idWeb("usuarios", "username", provUser), (consultar("SELECT usuario_id FROM proveedores WHERE id = ?", provA).single()[0] as Number).toLong())
            val proveedorCuenta = entrar(provUser, "2468", Rol.PROVEEDOR)
            assertEquals("ACTIVO", db.proveedorQueries.selectPorCodigo(fichas[0]).executeAsOne().estado)
            panel.enviar("/admin/proveedores/$provA/estado", mapOf("_method" to "PATCH", "estado" to "suspendido", "motivo_estado" to "Prueba integrada"))
            // Suspensión conjunta (regla del panel): la cuenta vinculada pierde el acceso remoto y deberá verificarse de nuevo.
            assertIs<RechazoServidorException>(servidor.autenticar(provUser, "2468").exceptionOrNull())
            assertIs<pe.ecolecta.domain.repository.SinSesionServidorException>(servidor.descargarDatos(proveedorCuenta).exceptionOrNull())
            assertTrue(k.get<UsuarioRepository>().obtenerPorId(proveedorCuenta)!!.requiereVerificacion)
            entrar("admin", pinAdmin, Rol.ADMIN)
            assertEquals("SUSPENDIDO", db.proveedorQueries.selectPorCodigo(fichas[0]).executeAsOne().estado)
            assertFalse(db.proveedorQueries.selectActivosPorZona(zonaLocal.id).executeAsList().any { it.codigo == fichas[0] })
        } finally {
            app.koin.get<io.ktor.client.HttpClient>().close()
            app.close()
            driver.close()
        }
    }
}
