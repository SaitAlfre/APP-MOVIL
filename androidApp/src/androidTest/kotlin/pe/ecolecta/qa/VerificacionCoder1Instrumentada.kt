package pe.ecolecta.qa

import android.app.Activity
import android.app.Instrumentation
import android.content.Intent
import android.graphics.Bitmap
import android.os.Bundle
import app.cash.sqldelight.driver.android.AndroidSqliteDriver
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.runBlocking
import org.json.JSONArray
import org.json.JSONObject
import org.koin.core.context.GlobalContext
import pe.ecolecta.BuildConfig
import pe.ecolecta.EcolectaApp
import pe.ecolecta.MainActivity
import pe.ecolecta.data.local.SchemaCuentasSeguras
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebRepositoryKtor
import pe.ecolecta.data.remote.crearHttpClient
import pe.ecolecta.data.repository.*
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.RelojSistema
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.security.ProtectorSecretos
import pe.ecolecta.domain.usecase.auth.*
import pe.ecolecta.domain.usecase.sync.EnviarCambiosServidorUseCase
import pe.ecolecta.domain.usecase.usuario.*
import java.io.File
import java.util.UUID
import kotlin.random.Random

/** QA Coder 1: base separada y backend local. No controla ventanas ni acredita interacción manual. */
class VerificacionCoder1Instrumentada : Instrumentation() {
    private var argumentos = Bundle()
    private val resultados = JSONArray()
    private val capturas = JSONArray()
    private var prefijo = ""
    private var fallo: String? = null
    private var catalogosComparados: JSONObject? = null

    override fun onCreate(arguments: Bundle?) {
        argumentos = arguments ?: Bundle()
        super.onCreate(arguments)
        start()
    }

    private fun comprobar(id: String, accion: () -> Unit) {
        try {
            accion()
            resultados.put(JSONObject().put("id", id).put("estado", "Passed"))
            sendStatus(0, Bundle().apply { putString("coder1_check", "$id Passed") })
        } catch (error: Throwable) {
            resultados.put(JSONObject().put("id", id).put("estado", "Failed"))
            throw error
        }
    }

    private fun captura(nombre: String) {
        waitForIdleSync()
        // El árbol puede estar listo durante la transición de Compose: esperar también al fotograma visible.
        uiAutomation.waitForIdle(250, 5_000)
        Thread.sleep(700)
        waitForIdleSync()
        val bitmap = checkNotNull(uiAutomation.takeScreenshot())
        val archivo = File(targetContext.getExternalFilesDir(null), "coder1-$nombre.png")
        archivo.outputStream().use { bitmap.compress(Bitmap.CompressFormat.PNG, 100, it) }
        bitmap.recycle()
        capturas.put(archivo.absolutePath)
    }

    override fun onStart() {
        try {
            check(BuildConfig.LOCAL_PREVIEW && BuildConfig.SERVIDOR_URL.startsWith("http://10.0.2.2:"))
            check(argumentos.getString("entorno") == "coder1-ficticio")
            startActivitySync(Intent(targetContext, MainActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
            val limite = System.currentTimeMillis() + 60_000
            while ((targetContext.applicationContext as EcolectaApp).estadoInicio.value != EcolectaApp.EstadoInicio.LISTO) {
                check(System.currentTimeMillis() < limite) { "La aplicación no terminó de preparar su entorno" }
                Thread.sleep(100)
            }
            val koin = GlobalContext.get()
            val protector = koin.get<ProtectorSecretos>()
            if (argumentos.getString("modo") == "segundo") {
                comprobar("INT-C1-cliente-independiente") {
                    runBlocking {
                        val previa = koin.get<SesionRepository>().observar().first()
                        check(previa == null || previa.usuario.username == "admin")
                        if (previa == null) {
                            val inicio = koin.get<IniciarSesionUseCase>()("admin", checkNotNull(argumentos.getString("pinPrueba"))).getOrThrow()
                            koin.get<SeleccionarRolUseCase>()(inicio.usuario, Rol.ADMIN).getOrThrow()
                        }
                        val sesion = checkNotNull(koin.get<SesionRepository>().observar().first())
                        val remoto = koin.get<pe.ecolecta.domain.repository.ServidorWebRepository>().descargarDatos(sesion.usuario.id).getOrThrow()
                        val local = koin.get<pe.ecolecta.domain.repository.DatosServidorLocalRepository>()
                        repeat(2) { local.aplicar(remoto) }
                        val usuarios = koin.get<pe.ecolecta.domain.repository.UsuarioRepository>()
                        val calidad = checkNotNull(usuarios.obtenerPorUsername("qa_c1_ed120b_1"))
                        val proveedor = checkNotNull(usuarios.obtenerPorUsername("qa_c1_ed120b_2"))
                        val zonaId = koin.get<pe.ecolecta.domain.repository.CuentasRepository>().zonaAsignada(calidad.id)
                        val zona = koin.get<pe.ecolecta.domain.repository.ZonaRepository>().observarTodas().first().single { it.id == zonaId }
                        val ficha = koin.get<pe.ecolecta.domain.repository.ProveedorRepository>().observarTodos().first().single { it.codigo == "QA-ED120B" }
                        check(calidad.nombres == "Cuenta ficticia qa_c1_ed120b 1 WEB26" && calidad.roles == listOf(Rol.CALIDAD))
                        check(zona.nombre == (argumentos.getString("zonaEsperada") ?: "QA SEGUNDO qa_c1_ed120b"))
                        val libre = argumentos.getString("vinculoEsperado") == "libre"
                        check(ficha.usuarioId == if (libre) null else proveedor.id)
                        check(ficha.dueno == "Responsable ficticio UI26" && ficha.telefono == "+51 900 000 026")
                        check(remoto.zonas.any { it.nombre == "QA WEB C1 20261026" })
                        catalogosComparados = JSONObject().put("codigo", ficha.codigo).put("responsable", ficha.dueno)
                            .put("telefono", ficha.telefono).put("cuentaVinculada", if (libre) "libre" else proveedor.username)
                            .put("cuentaCalidad", calidad.username).put("nombreCalidad", calidad.nombres)
                            .put("zonaCalidad", zona.nombre).put("proveedorServidorId", remoto.proveedores.single { it.codigo == ficha.codigo }.id)
                    }
                    InterfazCoder1(this).observar()
                    captura("cliente-independiente")
                }
            } else if (argumentos.getString("modo") == "bloqueo") {
                val sesion = runBlocking { koin.get<SesionRepository>().observar().first() }
                check(sesion == null || sesion.usuario.username == "admin" || sesion.usuario.username.startsWith("qa_c1_"))
                InterfazCoder1(this).bloqueo(koin, argumentos, ::comprobar, ::captura)
            } else if (argumentos.getString("modo") in setOf("interfaz", "cierre")) {
                runBlocking {
                    val previa = koin.get<SesionRepository>().observar().first()
                    check(previa == null || previa.usuario.username == "admin" || previa.usuario.username.startsWith("qa_c1_"))
                    if (previa?.rolActivo != Rol.ADMIN) {
                        koin.get<CerrarSesionUseCase>()()
                        val inicio = koin.get<IniciarSesionUseCase>()("admin", checkNotNull(argumentos.getString("pinPrueba"))).getOrThrow()
                        koin.get<SeleccionarRolUseCase>()(inicio.usuario, Rol.ADMIN).getOrThrow()
                    }
                }
                comprobar("INT-C1-web-a-android") {
                    runBlocking {
                        val sesion = checkNotNull(koin.get<SesionRepository>().observar().first())
                        val datos = koin.get<pe.ecolecta.domain.repository.ServidorWebRepository>().descargarDatos(sesion.usuario.id).getOrThrow()
                        koin.get<pe.ecolecta.domain.repository.DatosServidorLocalRepository>().aplicar(datos)
                        check(datos.zonas.any { it.nombre == "QA WEB C1 20261026" })
                        check(koin.get<pe.ecolecta.domain.repository.UsuarioRepository>().obtenerPorUsername("qa_c1_ed120b_1")?.nombres == "Cuenta ficticia qa_c1_ed120b 1 WEB26")
                    }
                }
                val interfaz = InterfazCoder1(this)
                if (argumentos.getString("modo") == "cierre") {
                    interfaz.cierre(koin, ::comprobar, ::captura)
                } else {
                    interfaz.formularios(koin, ::comprobar, ::captura)
                    interfaz.accesos(koin, argumentos, ::comprobar, ::captura)
                }
            } else if (argumentos.getString("modo") == "reabrir") {
                comprobar("NAT-C1-07-sesion-persistida") {
                    val sesion = runBlocking { koin.get<SesionRepository>().observar().first() }
                    check(sesion?.rolActivo == Rol.ADMIN && sesion.usuario.username == "admin")
                    captura("reapertura")
                }
            } else {
                comprobar("NAT-C1-01-keystore-real") {
                    val cifrado = protector.proteger("valor-ficticio", "qa:coder1")
                    check(cifrado.startsWith("ks1:") && cifrado != "valor-ficticio")
                    check(protector.abrir(cifrado, "qa:coder1") == "valor-ficticio")
                    check(runCatching { protector.abrir(cifrado, "qa:otra") }.isFailure)
                    check(protector.proteger("valor-ficticio", "qa:coder1") != cifrado)
                }
                comprobar("NAT-C1-02-cuatro-roles") {
                    check(Rol.entries.map { it.name }.toSet() == setOf("ADMIN", "ACOPIADOR", "CALIDAD", "PROVEEDOR"))
                }
                roundTrip(protector)
                comprobar("NAT-C1-06-login-y-navegacion") {
                    runBlocking {
                        val previa = koin.get<SesionRepository>().observar().first()
                        check(previa == null || previa.usuario.username == "admin") { "Hay otra sesión: no se modifica" }
                        koin.get<CerrarSesionUseCase>()()
                    }
                    captura("login")
                    runBlocking {
                        val inicio = koin.get<IniciarSesionUseCase>()("admin", checkNotNull(argumentos.getString("pinPrueba"))).getOrThrow()
                        koin.get<SeleccionarRolUseCase>()(inicio.usuario, Rol.ADMIN).getOrThrow()
                        check(koin.get<SesionRepository>().observar().first()?.rolActivo == Rol.ADMIN)
                    }
                    captura("administrador")
                }
            }
            terminar(true)
        } catch (error: Throwable) {
            runCatching { InterfazCoder1(this).observar() }
            runCatching { captura("ui-fallo") }
            val origen = error.stackTrace.firstOrNull { it.className.startsWith("pe.ecolecta") }
            fallo = "${error.javaClass.simpleName} en ${origen?.fileName}:${origen?.lineNumber}: ${error.message?.take(180)}"
            terminar(false)
        }
    }

    private fun roundTrip(protector: ProtectorSecretos) = runBlocking {
        prefijo = "qa_c1_" + UUID.randomUUID().toString().replace("-", "").take(6)
        val nombre1 = "$prefijo-uno.db"
        val nombre2 = "$prefijo-dos.db"
        val driver = AndroidSqliteDriver(SchemaCuentasSeguras, targetContext, nombre1)
        val segundo = AndroidSqliteDriver(SchemaCuentasSeguras, targetContext, nombre2)
        val db = EcolectaDatabase(driver)
        val db2 = EcolectaDatabase(segundo)
        val http = crearHttpClient()
        val reloj = RelojSistema()
        val hasher = Pbkdf2PinHasher()
        val usuarios = SqlDelightUsuarioRepository(db, Dispatchers.IO)
        val local = SqlDelightDatosServidorRepository(db, reloj, Dispatchers.IO)
        val local2 = SqlDelightDatosServidorRepository(db2, reloj, Dispatchers.IO)
        val cola = SqlDelightCambiosLocalesRepository(db, Dispatchers.IO, protector)
        val cola2 = SqlDelightCambiosLocalesRepository(db2, Dispatchers.IO, protector)
        val servidor = ServidorWebRepositoryKtor(BuildConfig.SERVIDOR_URL, http, db, usuarios, reloj, Dispatchers.IO, "QA Coder 1", protector)
        val servidor2 = ServidorWebRepositoryKtor(BuildConfig.SERVIDOR_URL, http, db2, SqlDelightUsuarioRepository(db2, Dispatchers.IO), reloj, Dispatchers.IO, "QA Coder 1 segundo", protector)
        try {
            val remota = servidor.autenticar("admin", checkNotNull(argumentos.getString("pinPrueba"))).getOrThrow()
            val hash = hasher.crearHash(checkNotNull(argumentos.getString("pinPrueba")))
            val adminId = local.guardarCuenta(checkNotNull(remota.cuenta), hash.hash, hash.salt, 1)
            servidor.guardarSesion(adminId, remota)
            local.aplicar(servidor.descargarDatos(adminId).getOrThrow())
            db.sesionQueries.establecer(adminId, "ADMIN")
            val zonas = SqlDelightZonaRepository(db, Dispatchers.IO)
            val vehiculos = SqlDelightVehiculoRepository(db, Dispatchers.IO)
            val proveedores = SqlDelightProveedorRepository(db, Dispatchers.IO)
            val zona = Zona(UUID.randomUUID().toString(), "QA CODER1 $prefijo", true)
            zonas.insertar(zona)
            vehiculos.insertar(Vehiculo.crear(UUID.randomUUID().toString(), "Vehículo ficticio $prefijo", "Q" + prefijo.takeLast(6).uppercase(), true).getOrThrow())
            val guardar = GuardarCuentaUseCase(usuarios, SqlDelightCuentasRepository(db, Dispatchers.IO),
                ReglasCuenta(usuarios, zonas, proveedores, SqlDelightJornadaRepository(db, Dispatchers.IO)), proveedores,
                SqlDelightAuditoriaRepository(db, Dispatchers.IO), hasher, reloj,
                object : DeviceIdProvider { override fun obtenerId() = "qa-coder1" }, cola)
            val ids = mutableMapOf<Rol, String>()
            val codigo = ("QA-" + prefijo.takeLast(6).uppercase()).padEnd(50, 'X')
            for ((indice, rol) in listOf(Rol.ACOPIADOR, Rol.CALIDAD, Rol.PROVEEDOR).withIndex()) {
                val documento = (90_000_000 + Random.nextInt(9_000_000)).toString()
                ids[rol] = guardar(DatosCuenta(null, "${prefijo}_$indice", "Cuenta ficticia $prefijo $indice", documento,
                    setOf(rol), true, if (rol.requiereZona) zona.id else null, null, "1357", "1357",
                    if (rol == Rol.PROVEEDOR) FichaNueva(codigo, "Finca ficticia $prefijo".padEnd(150, 'X'), ("FICHA-$documento").padEnd(20, 'X'), zona.id, 1000, 200.01, "T".repeat(30), "A".repeat(255)) else null), adminId).getOrThrow()
            }
            comprobar("NAT-C1-03-altas-y-descarga-segundo-dispositivo") {
                runBlocking {
                    check(EnviarCambiosServidorUseCase(servidor, cola)().enviados == 6)
                    check(cola.pendientes().isEmpty())
                    val exportado = servidor.descargarDatos(adminId).getOrThrow()
                    local.aplicar(exportado)
                    local2.aplicar(exportado)
                    val ficha = db2.proveedorQueries.selectPorCodigo(codigo).executeAsOne()
                    check(ficha.dueno == "Cuenta ficticia $prefijo 2")
                    check(ficha.codigo.length == 50 && ficha.nombres.length == 150 && ficha.dni.length == 20)
                    check(ficha.telefono?.length == 30 && ficha.direccion?.length == 255 && ficha.tachos == 1000L && ficha.capacidad_tacho_l == 200.01)
                    val cuenta = db2.usuarioQueries.selectPorUsername("${prefijo}_2").executeAsOne()
                    check(ficha.usuario_id == cuenta.id)
                    check(db2.usuarioZonaQueries.selectZonaId("web-usuario-" + exportado.usuarios.first { it.username == "${prefijo}_1" }.id).executeAsOne() == db2.zonaQueries.selectPorNombre(zona.nombre).executeAsOne().id)
                    check(db.tokenServidorQueries.porUsuario(adminId).executeAsOne().token.startsWith("ks1:"))
                }
            }
            comprobar("NAT-C1-04-sin-red-y-reintento") {
                runBlocking {
                    zonas.actualizar(zona.copy(nombre = "QA OFFLINE $prefijo"))
                    val sinRed = ServidorWebRepositoryKtor("http://10.0.2.2:9", http, db, usuarios, reloj, Dispatchers.IO, protector = protector)
                    check(EnviarCambiosServidorUseCase(sinRed, cola)().sinConexion)
                    check(db.cambioPendienteQueries.porClave("zona", zona.id).executeAsOne().estado == "PENDING")
                    check(EnviarCambiosServidorUseCase(servidor, cola)().enviados == 1)
                    check(EnviarCambiosServidorUseCase(servidor, cola)().enviados == 0)
                    local.aplicar(servidor.descargarDatos(adminId).getOrThrow())
                }
            }
            comprobar("NAT-C1-05-conflicto-conservado") {
                runBlocking {
                    val remota2 = servidor2.autenticar("admin", checkNotNull(argumentos.getString("pinPrueba"))).getOrThrow()
                    val admin2 = local2.guardarCuenta(checkNotNull(remota2.cuenta), hash.hash, hash.salt, 1)
                    servidor2.guardarSesion(admin2, remota2)
                    local2.aplicar(servidor2.descargarDatos(admin2).getOrThrow())
                    db2.sesionQueries.establecer(admin2, "ADMIN")
                    val otraZona = db2.zonaQueries.selectPorNombre("QA OFFLINE $prefijo").executeAsOne()
                    SqlDelightZonaRepository(db2, Dispatchers.IO).actualizar(Zona(otraZona.id, "QA SEGUNDO $prefijo", true))
                    check(EnviarCambiosServidorUseCase(servidor2, cola2)().enviados == 1)
                    zonas.actualizar(zona.copy(nombre = "QA PRIMERO $prefijo"))
                    check(EnviarCambiosServidorUseCase(servidor, cola)().conError == 1)
                    local.aplicar(servidor.descargarDatos(adminId).getOrThrow())
                    check(db.zonaQueries.selectPorId(zona.id).executeAsOne().nombre == "QA PRIMERO $prefijo")
                    check(db.cambioPendienteQueries.porClave("zona", zona.id).executeAsOne().estado == "ERROR")
                    servidor2.desvincular(admin2)
                }
            }
            servidor.desvincular(adminId)
        } finally {
            http.close()
            driver.close()
            segundo.close()
            targetContext.deleteDatabase(nombre1)
            targetContext.deleteDatabase(nombre2)
        }
    }

    private fun terminar(exito: Boolean) {
        val resultado = JSONObject().put("exito", exito).put("checks", resultados).put("capturas", capturas)
            .put("prefijoFicticio", prefijo).put("ui", if (argumentos.getString("modo") in setOf("interfaz", "cierre", "bloqueo")) "Interacción gráfica automatizada mediante accesibilidad de Android; no operación manual en Android Studio" else "captura real; autenticación invocada por casos de uso, sin pulsaciones manuales")
            .put("fallo", fallo)
            .put("catalogosComparados", catalogosComparados)
        finish(if (exito) Activity.RESULT_OK else Activity.RESULT_CANCELED,
            Bundle().apply { putString("coder1_json", resultado.toString()) })
    }
}
