package pe.ecolecta.qa

import android.app.Instrumentation
import android.os.Bundle
import android.os.Build
import android.view.KeyEvent
import android.view.accessibility.AccessibilityNodeInfo
import java.io.File
import pe.ecolecta.BuildConfig
import org.koin.core.Koin
import kotlinx.coroutines.runBlocking
import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.usecase.sync.EnviarCambiosServidorUseCase

/** Pruebas gráficas reales del preview ficticio; no controla Android Studio ni otros paquetes. */
internal class InterfazCoder1(private val instrumentacion: Instrumentation) {
    private fun nodos(raiz: AccessibilityNodeInfo): List<AccessibilityNodeInfo> = buildList {
        add(raiz)
        repeat(raiz.childCount) { indice -> raiz.getChild(indice)?.let { addAll(nodos(it)) } }
    }
    private fun arbol(): List<AccessibilityNodeInfo> {
        repeat(60) {
            if (Build.VERSION.SDK_INT >= 33) instrumentacion.uiAutomation.clearCache()
            val raiz = instrumentacion.uiAutomation.rootInActiveWindow
            if (raiz?.packageName?.toString() == BuildConfig.APPLICATION_ID) return nodos(raiz)
            Thread.sleep(100)
        }
        error("No estuvo disponible el árbol de accesibilidad del preview durante 6 segundos")
    }
    fun observar() {
        instrumentacion.waitForIdleSync()
        val texto = arbol().joinToString("\n") { nodo ->
            "${nodo.className}|${if (nodo.isPassword) "<OCULTO>" else nodo.text?.toString().orEmpty()}|${nodo.contentDescription?.toString().orEmpty()}|click=${nodo.isClickable}|edit=${nodo.isEditable}|scroll=${nodo.isScrollable}"
        }
        File(instrumentacion.targetContext.getExternalFilesDir(null), "coder1-ui-arbol.txt").writeText(texto)
    }
    private fun coincide(nodo: AccessibilityNodeInfo, etiqueta: String) =
        nodo.text?.toString() == etiqueta || nodo.contentDescription?.toString() == etiqueta
    private fun espera(condicion: () -> Boolean) {
        val hasta = System.currentTimeMillis() + 20_000
        while (!condicion()) {
            if (System.currentTimeMillis() >= hasta) { observar(); error("La condición de interfaz no se alcanzó; ver árbol QA") }
            Thread.sleep(150)
        }
        instrumentacion.waitForIdleSync()
    }
    private fun pulsar(etiqueta: String) {
        val hasta = System.currentTimeMillis() + 15_000
        while (System.currentTimeMillis() < hasta) {
            for (nodo in arbol().filter { coincide(it, etiqueta) }) {
                var pulsable: AccessibilityNodeInfo? = nodo
                while (pulsable != null && !pulsable.isClickable) pulsable = pulsable.parent
                if (pulsable?.isEnabled == true) {
                    if (pulsable.performAction(AccessibilityNodeInfo.ACTION_CLICK)) {
                        instrumentacion.waitForIdleSync()
                        return
                    }
                }
            }
            arbol().firstOrNull { it.isScrollable }?.performAction(AccessibilityNodeInfo.ACTION_SCROLL_FORWARD)
            instrumentacion.waitForIdleSync()
            Thread.sleep(500)
        }
        observar();error("No se encontró control pulsable: $etiqueta")
    }
    private fun volver() {
        instrumentacion.sendKeyDownUpSync(KeyEvent.KEYCODE_BACK)
        instrumentacion.waitForIdleSync()
    }
    private fun hay(etiqueta: String) = arbol().any { coincide(it, etiqueta) }
    private fun escribir(nodo: AccessibilityNodeInfo, valor: String) {
        check(nodo.performAction(AccessibilityNodeInfo.ACTION_SET_TEXT, Bundle().apply {
            putCharSequence(AccessibilityNodeInfo.ACTION_ARGUMENT_SET_TEXT_CHARSEQUENCE, valor)
        })) { "El campo no admitió la entrada de prueba" }
        instrumentacion.waitForIdleSync()
    }

    fun bloqueo(koin: Koin, argumentos: Bundle, comprobar: (String, () -> Unit) -> Unit, captura: (String) -> Unit) {
        comprobar("UI-C1-cuenta-bloqueada") {
            runBlocking { koin.get<CerrarSesionUseCase>()() }
            espera { hay("Ingresar") && arbol().count { it.isEditable } == 2 }
            val campos = arbol().filter { it.isEditable }
            escribir(campos[0], "qa_c1_ed120b_1")
            escribir(campos[1], "1357")
            pulsar("Ingresar")
            espera { arbol().any { it.text?.toString()?.contains("bloque", ignoreCase = true) == true } }
            check(runBlocking { koin.get<SesionRepository>().observar().first() } == null)
            captura("ui-cuenta-bloqueada")
        }
        comprobar("UI-C1-restaurar-admin") {
            val campos = arbol().filter { it.isEditable }
            escribir(campos[0], "admin")
            escribir(campos[1], checkNotNull(argumentos.getString("pinPrueba")))
            pulsar("Ingresar")
            espera { runBlocking { koin.get<SesionRepository>().observar().first()?.rolActivo == Rol.ADMIN } }
            captura("ui-admin-final")
        }
    }

    fun formularios(koin: Koin, comprobar: (String, () -> Unit) -> Unit, captura: (String) -> Unit) {
        val sesion = runBlocking { koin.get<SesionRepository>().observar().first() }
        check(sesion?.usuario?.username == "admin" && sesion.rolActivo == Rol.ADMIN)
        val sufijo = System.currentTimeMillis().toString().takeLast(6)
        val nombreZona = "QA UI ZONA $sufijo"
        val nombreVehiculo = "QA UI VEHICULO $sufijo"
        val placa = "U$sufijo"
        val nuevosIds = mutableSetOf<String>()
        comprobar("UI-C1-01-perfil-y-usuarios") {
            pulsar("Perfil"); espera { hay("Usuarios y roles") }; captura("ui-perfil")
            pulsar("Usuarios y roles"); espera { hay("Nueva cuenta") }
            captura("ui-usuarios")
            pulsar("Nueva cuenta"); espera { hay("Nueva cuenta") && arbol().any { it.isEditable } }
            observar(); captura("ui-cuenta-formulario")
            // El botón puede estar al final del formulario; se desplaza mediante accesibilidad.
            pulsar("Crear cuenta"); espera { hay("Escribe los nombres y apellidos.") }
            captura("ui-cuenta-rechazada")
            volver(); volver()
        }
        comprobar("UI-C1-02-zona-formulario-y-error") {
            pulsar("Zonas y rutas"); espera { hay("Nueva zona") }
            pulsar("Nueva zona"); espera { arbol().any { it.isEditable } }
            pulsar("Crear zona"); espera { hay("Escribe el nombre de la zona.") }
            captura("ui-zona-rechazada")
            escribir(arbol().first { it.isEditable }, nombreZona)
            pulsar("Crear zona")
            espera { hay("Nueva zona") }
            val zona = runBlocking { koin.get<ZonaRepository>().observarTodas().first().first { it.nombre == nombreZona } }
            nuevosIds += zona.id
            captura("ui-zona-creada")
            volver()
        }
        comprobar("UI-C1-03-vehiculo-formulario-y-error") {
            pulsar("Vehículos"); espera { hay("Nuevo vehículo") }
            pulsar("Nuevo vehículo"); espera { arbol().any { it.isEditable } }
            pulsar("Registrar vehículo"); espera { hay("Escribe el nombre y la placa.") }
            captura("ui-vehiculo-rechazado")
            val campos = arbol().filter { it.isEditable }
            check(campos.size == 2)
            escribir(campos[0], nombreVehiculo)
            escribir(campos[1], placa)
            pulsar("Registrar vehículo")
            espera { hay("Nuevo vehículo") }
            val vehiculo = runBlocking { koin.get<VehiculoRepository>().observarTodos().first().first { it.placa == placa } }
            nuevosIds += vehiculo.id
            captura("ui-vehiculo-creado")
            volver()
        }
        comprobar("UI-C1-04-ficha-proveedor") {
            pulsar("Inicio"); pulsar("Proveedores")
            espera { arbol().any { it.isEditable } }
            val buscador = arbol().first { it.isEditable }
            check(buscador.performAction(AccessibilityNodeInfo.ACTION_SET_TEXT, Bundle().apply {
                putCharSequence(AccessibilityNodeInfo.ACTION_ARGUMENT_SET_TEXT_CHARSEQUENCE, "QA-ED120B")
            }))
            espera { hay("Finca ficticia qa_c1_ed120b") }
            pulsar("Finca ficticia qa_c1_ed120b")
            espera { hay("Editar datos del proveedor") }
            pulsar("Editar datos del proveedor")
            espera { arbol().count { it.isEditable } >= 3 }
            observar(); captura("ui-proveedor-formulario")
            val ficha = runBlocking { koin.get<ProveedorRepository>().observarTodos().first().first { it.codigo == "QA-ED120B" } }
            espera { arbol().any { it.isEditable && it.text?.toString() == ficha.dueno } }
            escribir(arbol().first { it.isEditable && it.text?.toString() == ficha.dueno }, "Responsable ficticio UI26")
            pulsar("Guardar cambios")
            espera { runBlocking { koin.get<ProveedorRepository>().obtenerPorId(ficha.id)?.dueno == "Responsable ficticio UI26" } }
            nuevosIds += ficha.id
        }
        comprobar("INT-C1-ui-android-web") {
            runBlocking {
                val cola = koin.get<CambiosLocalesRepository>()
                val propia = object : CambiosLocalesRepository by cola {
                    override suspend fun pendientes() = cola.pendientes().filter { it.localId in nuevosIds && it.entidad in setOf("zona", "vehiculo", "proveedor") }
                }
                val servidor = koin.get<ServidorWebRepository>()
                val envio = EnviarCambiosServidorUseCase(servidor, propia)()
                check(envio.conError == 0 && !envio.sinConexion)
                // La app mantiene su sincronización automática; este envío explícito solo toma IDs C1.
                check(propia.pendientes().isEmpty())
                val remoto = servidor.descargarDatos(checkNotNull(sesion).usuario.id).getOrThrow()
                check(remoto.zonas.any { it.nombre == nombreZona })
                check(remoto.vehiculos.any { it.placa == placa })
                check(remoto.proveedores.first { it.codigo == "QA-ED120B" }.dueno == "Responsable ficticio UI26")
            }
            captura("ui-envio-confirmado")
        }
    }

    private fun principio() {
        repeat(12) {
            arbol().firstOrNull { it.isScrollable }?.performAction(AccessibilityNodeInfo.ACTION_SCROLL_BACKWARD)
            instrumentacion.waitForIdleSync()
            Thread.sleep(150)
        }
    }

    private fun campo(etiqueta: String, valor: String) {
        principio()
        repeat(25) {
            val lista = arbol()
            val indice = lista.indexOfFirst { coincide(it, etiqueta) }
            if (indice >= 0) {
                val editable = lista.drop(indice + 1).firstOrNull { it.isEditable }
                if (editable != null && editable.isVisibleToUser) { escribir(editable, valor); return }
            }
            lista.firstOrNull { it.isScrollable }?.performAction(AccessibilityNodeInfo.ACTION_SCROLL_FORWARD)
            instrumentacion.waitForIdleSync()
            Thread.sleep(250)
        }
        observar(); error("No se encontró el campo QA: $etiqueta")
    }

    private fun interruptor(etiqueta: String, estado: Boolean) {
        principio()
        repeat(25) {
            val lista = arbol()
            val indice = lista.indexOfFirst { coincide(it, etiqueta) }
            val control = if (indice >= 0) lista.drop(indice + 1).firstOrNull { it.isCheckable } else null
            if (control?.isVisibleToUser == true) {
                if (control.isChecked != estado) check(control.performAction(AccessibilityNodeInfo.ACTION_CLICK))
                instrumentacion.waitForIdleSync(); return
            }
            lista.firstOrNull { it.isScrollable }?.performAction(AccessibilityNodeInfo.ACTION_SCROLL_FORWARD)
            instrumentacion.waitForIdleSync()
            Thread.sleep(250)
        }
        observar(); error("No se encontró el interruptor QA: $etiqueta")
    }

    /** Altas y estados por los controles reales; solo registros ficticios creados por esta suite. */
    fun cierre(koin: Koin, comprobar: (String, () -> Unit) -> Unit, captura: (String) -> Unit) {
        check(runBlocking { koin.get<SesionRepository>().observar().first()?.rolActivo } == Rol.ADMIN)
        val sufijo = System.currentTimeMillis().toString().takeLast(7)
        val username = "qa_c1_ui_$sufijo"
        var nombre = "QA CUENTA UI $sufijo"
        val ids = mutableSetOf<String>()
        var usuarioId = ""
        comprobar("UI-C1-05-alta-cuenta") {
            pulsar("Perfil"); pulsar("Usuarios y roles"); pulsar("Nueva cuenta")
            campo("Nombres y apellidos", nombre)
            campo("DNI de la persona", "9$sufijo")
            campo("Usuario para iniciar sesión", username)
            principio(); pulsar("Acopiador")
            campo("PIN de 4 dígitos", "1357"); campo("Confirmar PIN", "1357")
            pulsar("Crear cuenta")
            espera { runBlocking { koin.get<UsuarioRepository>().obtenerPorUsername(username) != null } && arbol().count { it.isEditable } == 1 }
            val usuario = runBlocking { koin.get<UsuarioRepository>().obtenerPorUsername(username) }
            checkNotNull(usuario); check(usuario.roles == listOf(Rol.ACOPIADOR) && usuario.activo)
            usuarioId = usuario.id; ids += usuario.id
            escribir(arbol().first { it.isEditable }, username)
            espera { hay(nombre) }; captura("ui-cuenta-creada")
        }
        comprobar("UI-C1-06-edicion-cuenta-y-pin") {
            pulsar(nombre); espera { hay("Editar cuenta") }
            nombre += " EDITADA"
            campo("Nombres y apellidos", nombre)
            interruptor("Restablecer PIN", true)
            campo("PIN nuevo", "2468"); campo("Confirmar PIN", "2468")
            pulsar("Guardar cambios"); espera { hay("Nueva cuenta") }
            espera { runBlocking { koin.get<UsuarioRepository>().obtenerPorId(usuarioId)?.nombres == nombre } }
            captura("ui-cuenta-editada")
        }
        comprobar("UI-C1-07-estados-cuenta") {
            for (estado in listOf(false, true)) {
                principio(); pulsar(nombre); espera { hay("Editar cuenta") }
                interruptor("Cuenta activa", estado); pulsar("Guardar cambios"); espera { hay("Nueva cuenta") }
                espera { runBlocking { koin.get<UsuarioRepository>().obtenerPorId(usuarioId)?.activo == estado } }
                captura(if (estado) "ui-cuenta-reactivada" else "ui-cuenta-desactivada")
            }
            volver()
        }
        comprobar("UI-C1-08-estados-zona-libre") {
            val zona = runBlocking { koin.get<ZonaRepository>().observarTodas().first().first { it.nombre.startsWith("QA UI ZONA ") } }
            var zonaNombre = zona.nombre; ids += zona.id
            pulsar("Zonas y rutas")
            for (estado in listOf(false, true)) {
                principio(); pulsar(zonaNombre); espera { hay("Editar zona") }
                if (!estado) { zonaNombre += " EDITADA"; campo("Nombre de la zona", zonaNombre) }
                interruptor("Zona activa", estado); pulsar("Guardar cambios"); espera { hay("Nueva zona") }
                espera { runBlocking { koin.get<ZonaRepository>().observarTodas().first().any { it.id == zona.id && it.activo == estado && it.nombre == zonaNombre } } }
                captura(if (estado) "ui-zona-reactivada" else "ui-zona-desactivada")
            }
            volver()
        }
        comprobar("UI-C1-09-estados-vehiculo-libre") {
            val vehiculo = runBlocking { koin.get<VehiculoRepository>().observarTodos().first().first { it.nombre.startsWith("QA UI VEHICULO ") } }
            var vehiculoNombre = vehiculo.nombre; ids += vehiculo.id
            pulsar("Vehículos")
            for (estado in listOf(false, true)) {
                principio(); pulsar(vehiculoNombre); espera { hay("Editar vehículo") }
                if (!estado) { vehiculoNombre += " EDITADO"; campo("Nombre", vehiculoNombre) }
                interruptor("Vehículo activo", estado); pulsar("Guardar cambios"); espera { hay("Nuevo vehículo") }
                espera { runBlocking { koin.get<VehiculoRepository>().observarTodos().first().any { it.id == vehiculo.id && it.activo == estado && it.nombre == vehiculoNombre } } }
                captura(if (estado) "ui-vehiculo-reactivado" else "ui-vehiculo-desactivado")
            }
            volver()
        }
        comprobar("INT-C1-cierre-ui-web-pin-y-reintento") {
            runBlocking {
                val cola = koin.get<CambiosLocalesRepository>()
                val propia = object : CambiosLocalesRepository by cola {
                    override suspend fun pendientes() = cola.pendientes().filter { it.localId in ids && it.entidad in setOf("usuario", "zona", "vehiculo") }
                }
                val servidor = koin.get<ServidorWebRepository>()
                val envio = EnviarCambiosServidorUseCase(servidor, propia)()
                check(envio.conError == 0 && !envio.sinConexion && propia.pendientes().isEmpty())
                check(koin.get<pe.ecolecta.data.local.db.EcolectaDatabase>().cambioPendienteQueries.errores().executeAsList().none { it.local_id in ids })
                val sesion = checkNotNull(koin.get<SesionRepository>().observar().first())
                val remoto = servidor.descargarDatos(sesion.usuario.id).getOrThrow()
                check(remoto.usuarios.single { it.username == username }.let { it.nombres == nombre && it.activo && it.roles == listOf("acopiador") })
                check(servidor.autenticar(username, "1357").isFailure)
                check(servidor.autenticar(username, "2468").isSuccess)
                val repetido = EnviarCambiosServidorUseCase(servidor, propia)()
                check(repetido.enviados == 0 && repetido.conError == 0)
                check(servidor.descargarDatos(sesion.usuario.id).getOrThrow().usuarios.count { it.username == username } == 1)
            }
            captura("ui-cierre-confirmado")
        }
    }

    fun accesos(koin: Koin, argumentos: Bundle, comprobar: (String, () -> Unit) -> Unit, captura: (String) -> Unit) {
        for ((indice, rol) in listOf(Rol.ACOPIADOR, Rol.CALIDAD, Rol.PROVEEDOR, Rol.ADMIN).withIndex()) {
            comprobar("UI-C1-rol-${rol.name.lowercase()}") {
                runBlocking { koin.get<CerrarSesionUseCase>()() }
                espera { hay("Ingresar") && arbol().count { it.isEditable } == 2 }
                val entradas = arbol().filter { it.isEditable }
                val username = if (rol == Rol.ADMIN) "admin" else "qa_c1_ed120b_$indice"
                check(entradas[0].performAction(AccessibilityNodeInfo.ACTION_SET_TEXT, Bundle().apply { putCharSequence(AccessibilityNodeInfo.ACTION_ARGUMENT_SET_TEXT_CHARSEQUENCE, username) }))
                val pin = if (rol == Rol.ADMIN) checkNotNull(argumentos.getString("pinPrueba")) else argumentos.getString("pinCuentas") ?: "1357"
                check(entradas[1].performAction(AccessibilityNodeInfo.ACTION_SET_TEXT, Bundle().apply { putCharSequence(AccessibilityNodeInfo.ACTION_ARGUMENT_SET_TEXT_CHARSEQUENCE, pin) }))
                pulsar("Ingresar")
                espera { runBlocking { koin.get<SesionRepository>().observar().first()?.let { it.usuario.username == username && it.rolActivo == rol } == true } }
                captura("ui-${rol.name.lowercase()}")
                check(rol == Rol.ADMIN || !hay("Usuarios y roles")) { "Otro perfil muestra administración de cuentas" }
            }
        }
    }
}
