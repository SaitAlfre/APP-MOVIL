package pe.ecolecta.presentation.acopiador

import androidx.lifecycle.ViewModelStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.awaitCancellation
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.ConfiguracionJornada
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeGestionPortalRepository
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.InicioAcopiador
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaTerminadaHoyUseCase
import pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ReanudarJornadaSiExisteUseCase
import pe.ecolecta.domain.usecase.jornada.ResolverInicioAcopiadorUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeViewModel
import pe.ecolecta.presentation.acopiador.onboarding.SeleccionZonaVehiculoViewModel
import pe.ecolecta.presentation.acopiador.perfil.textoConfirmacionCierreSesion
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.pantallaInicialAcopiador
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Reabrir la app con la sesión ACOPIADOR ya guardada (el caso del video: salir y volver a entrar).
 * Cada estado debe llevar a una pantalla que explique qué pasa y deje continuar o salir; nunca a una
 * selección genérica con "Abrir jornada" apagado.
 */
@OptIn(kotlinx.coroutines.ExperimentalCoroutinesApi::class)
class InicioAcopiadorTest {
    private val dispatcher = StandardTestDispatcher()
    private val store = ViewModelStore()
    private val reloj = FakeReloj()
    private val ahora = reloj.ahora().toEpochMilliseconds()
    private val sesiones = InMemorySesionRepository()
    private val jornadas = FakeJornadaRepository()
    private val enCurso = InMemoryJornadaEnCursoRepository()
    private val entregas = FakeEntregaRepository()
    private val auditoria = FakeAuditoriaRepository()
    private val zonasActivas = MutableStateFlow(listOf(Zona("z1", "FAON-MARKAPAJO", true), Zona("z2", "MORO VIEJO-PANCHA", true)))
    private var zonaAsignada: String? = null
    private var zonaAsignadaNuncaResponde = false

    private val zonas = object : ZonaRepository {
        override fun observarTodas() = zonasActivas
        override fun observarActivas() = zonasActivas.map { it.filter(Zona::activo) }
        override suspend fun obtenerPorId(id: String) = zonasActivas.value.firstOrNull { it.id == id }
        override suspend fun insertar(zona: Zona) = Unit
        override suspend fun actualizar(zona: Zona) = Unit
        override suspend fun desactivar(id: String) = Unit
        override suspend fun contarProveedoresEnZona(id: String) = 0L
    }
    private val vehiculos = object : VehiculoRepository {
        override fun observarTodos() = flowOf(listOf(Vehiculo("v1", "Camión 1", "V1A-123", true)))
        override fun observarActivos() = observarTodos()
        override suspend fun obtenerPorId(id: String): Vehiculo? = null
        override suspend fun existePlaca(placa: String, idExcluido: String) = false
        override suspend fun insertar(vehiculo: Vehiculo) = Unit
        override suspend fun actualizar(vehiculo: Vehiculo) = Unit
        override suspend fun desactivar(id: String) = Unit
    }
    private val cuentas = object : CuentasRepository {
        override fun observarZonasAsignadas() = flowOf(emptyMap<String, String>())
        override suspend fun zonaAsignada(usuarioId: String): String? {
            if (zonaAsignadaNuncaResponde) awaitCancellation()
            return zonaAsignada
        }
        override suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?) = Unit
        override suspend fun existeNombreZona(nombre: String, idExcluido: String) = false
        override suspend fun vehiculoEnJornadaAbierta(vehiculoId: String) = false
        override suspend fun crearCuenta(usuario: pe.ecolecta.domain.model.Usuario, zonaId: String?, fichaNueva: pe.ecolecta.domain.model.Proveedor?, fichaExistenteId: String?, auditorias: List<pe.ecolecta.domain.model.Auditoria>) = Unit
    }

    private val abrirJornada = AbrirJornadaUseCase(jornadas, enCurso, reloj)
    private val cerrarJornada = CerrarJornadaUseCase(
        jornadas, enCurso, reloj, auditoria, FakeDeviceIdProvider(),
    )
    private val cerrarSesion = CerrarSesionUseCase(sesiones)
    private val resolver = ResolverInicioAcopiadorUseCase(ReanudarJornadaSiExisteUseCase(jornadas, enCurso), jornadas, zonas, reloj)
    private val reabrir = ReabrirJornadaUseCase(
        jornadas, enCurso, auditoria,
        LoginOfflineUseCase(FakeUsuarioRepository(), Pbkdf2PinHasher(), auditoria, reloj, FakeDeviceIdProvider()),
        reloj, FakeDeviceIdProvider(), ConfiguracionJornada(plazoReaperturaMinutos = 30),
    )

    @BeforeTest fun preparar() { Dispatchers.setMain(dispatcher) }
    @AfterTest fun limpiar() { store.clear(); Dispatchers.resetMain() }

    private suspend fun iniciarSesion() {
        sesiones.iniciar(Sesion(Usuario("u1", "jperez", "Juan Pérez", "00000002", "", "", true, listOf(Rol.ACOPIADOR), 0), Rol.ACOPIADOR))
    }

    /** Lo que App.kt hace al arrancar con la sesión guardada; el proceso nuevo empieza sin jornada en memoria. */
    private suspend fun reabrirApp(): Pantalla {
        enCurso.limpiar()
        return pantallaInicialAcopiador(resolver("u1"))
    }

    private suspend fun entregaPendiente(jornadaId: String) {
        entregas.sembrar(Entrega.crear("e1", jornadaId, "p1", "u1", "z1", "v1", 142.0, 3, null, ahora, "d", null).getOrThrow())
    }

    private fun crearHome() = AcopiadorHomeViewModel(
        ObtenerJornadaEnCursoUseCase(enCurso),
        ObservarEntregasDeJornadaUseCase(entregas),
        ObtenerColaSyncUseCase(entregas),
        ListarProveedoresPorZonaUseCase(FakeProveedorRepository()),
        ObtenerSesionUseCase(sesiones),
        CorregirEntregaUseCase(entregas, reloj, FakeDeviceIdProvider(), ReglaEdicionEntrega(FakeGestionPortalRepository())),
        AnularEntregaUseCase(entregas, reloj, FakeDeviceIdProvider(), ReglaEdicionEntrega(FakeGestionPortalRepository())),
        ListarZonasUseCase(zonas),
        ListarVehiculosUseCase(vehiculos),
        cerrarJornada,
        ObtenerJornadaTerminadaHoyUseCase(jornadas, reloj),
        reabrir,
        cerrarSesion,
    ).also { store.put("home", it) }

    private fun crearSeleccion() = SeleccionZonaVehiculoViewModel(
        ListarZonasUseCase(zonas),
        ListarVehiculosUseCase(vehiculos),
        abrirJornada,
        ObtenerSesionUseCase(sesiones),
        reloj,
        cuentas,
        cerrarSesion,
    ).also { store.put("seleccion", it) }

    // --- Estado 1: jornada abierta -------------------------------------------------------------

    @Test
    fun `con jornada abierta reabrir la app la reanuda y va al inicio con sus entregas pendientes`() = runTest(dispatcher) {
        iniciarSesion()
        val jornada = abrirJornada("u1", "z1", "v1").getOrThrow()
        entregaPendiente(jornada.id)

        assertEquals(Pantalla.AcopiadorHome, reabrirApp())
        assertEquals(jornada, enCurso.observar().first(), "la jornada abierta vuelve a ser la jornada en curso")

        val home = crearHome()
        advanceUntilIdle()
        val estado = home.uiState.value
        assertTrue(estado.jornadaAbierta)
        assertFalse(estado.jornadaTerminada)
        assertEquals(142.0, estado.litrosHoy)
        assertEquals(1, estado.pendientesSync)
    }

    @Test
    fun `una jornada abierta olvidada de otro dia tambien se reanuda`() = runTest(dispatcher) {
        iniciarSesion()
        val ayer = AbrirJornadaUseCase(jornadas, enCurso, FakeReloj(fecha = LocalDate(2026, 1, 14))).getOrThrowAbrir()

        val inicio = resolver("u1")

        assertIs<InicioAcopiador.JornadaAbierta>(inicio)
        assertEquals(ayer.id, inicio.jornada.id)
    }

    // --- Estado 2: jornada de hoy cerrada ------------------------------------------------------

    @Test
    fun `con la jornada de hoy cerrada reabrir la app muestra el resumen terminado y no la seleccion`() = runTest(dispatcher) {
        iniciarSesion()
        val jornada = abrirJornada("u1", "z1", "v1").getOrThrow()
        entregaPendiente(jornada.id)
        cerrarJornada(jornada.id).getOrThrow()

        val inicio = resolver("u1")
        assertIs<InicioAcopiador.JornadaTerminadaHoy>(inicio)
        assertEquals(Pantalla.AcopiadorHome, reabrirApp())
        assertNull(enCurso.observar().first(), "una jornada cerrada nunca se presenta como en curso")

        val home = crearHome()
        advanceUntilIdle()
        val estado = home.uiState.value
        assertTrue(estado.jornadaTerminada)
        assertFalse(estado.jornadaAbierta)
        assertEquals(jornada.id, estado.jornadaId)
        assertEquals(142.0, estado.litrosHoy, "muestra los datos guardados de la jornada terminada")
        assertEquals(1, estado.pendientesSync, "y su sincronización pendiente")
        assertNotNull(estado.horaCierre)
    }

    @Test
    fun `desde el resumen terminado se reabre la misma jornada dentro del plazo`() = runTest(dispatcher) {
        iniciarSesion()
        val jornada = abrirJornada("u1", "z1", "v1").getOrThrow()
        entregaPendiente(jornada.id)
        cerrarJornada(jornada.id).getOrThrow()
        reabrirApp()
        val home = crearHome()
        advanceUntilIdle()

        home.solicitarReapertura()
        assertTrue(home.uiState.value.mostrarDialogoReapertura)
        assertFalse(home.uiState.value.reaperturaRequiereAdmin, "recién cerrada: dentro del plazo")
        home.confirmarReapertura("Cerré sin querer al guardar", "", "")
        advanceUntilIdle()

        val estado = home.uiState.value
        assertTrue(estado.jornadaAbierta)
        assertFalse(estado.mostrarDialogoReapertura)
        assertEquals(jornada.id, estado.jornadaId, "misma jornada, no una segunda del día")
        assertEquals(1, jornadas.observarTodas().first().size)
        assertEquals(1, estado.entregasHoy, "la entrega pendiente se conserva")
    }

    @Test
    fun `cerrar sesion con la jornada terminada conserva la entrega pendiente`() = runTest(dispatcher) {
        iniciarSesion()
        val jornada = abrirJornada("u1", "z1", "v1").getOrThrow()
        entregaPendiente(jornada.id)
        cerrarJornada(jornada.id).getOrThrow()
        reabrirApp()
        val home = crearHome()
        advanceUntilIdle()

        home.solicitarCierreSesion()
        home.confirmarCierreSesion()
        advanceUntilIdle()

        assertNull(sesiones.observar().first(), "la sesión se cierra aunque no haya jornada abierta")
        val guardada = entregas.obtenerPorId("e1")
        assertNotNull(guardada, "cerrar sesión no borra registros locales")
        assertEquals(SyncState.PENDING, guardada.syncState, "sigue pendiente de sincronizar")
        assertFalse(jornadas.obtenerPorId(jornada.id)!!.estaAbierta)
    }

    // --- Estado 3: sin zona activa -------------------------------------------------------------

    @Test
    fun `sin zonas activas reabrir la app lleva a la seleccion que explica el motivo y permite salir`() = runTest(dispatcher) {
        iniciarSesion()
        zonasActivas.value = listOf(Zona("z1", "FAON-MARKAPAJO", false))

        assertIs<InicioAcopiador.SinZonaActiva>(resolver("u1"))
        assertEquals(Pantalla.AcopiadorSeleccionZonaVehiculo, reabrirApp())

        val seleccion = crearSeleccion()
        advanceUntilIdle()
        val estado = seleccion.uiState.value
        assertFalse(estado.cargandoCatalogo)
        assertTrue(estado.sinZonasActivas)
        assertFalse(estado.puedeContinuar)

        seleccion.solicitarCierreSesion()
        seleccion.confirmarCierreSesion()
        advanceUntilIdle()
        assertNull(sesiones.observar().first(), "puede cerrar sesión aunque no pueda abrir jornada")
    }

    @Test
    fun `zona asignada desactivada se avisa y se puede elegir otra activa`() = runTest(dispatcher) {
        iniciarSesion()
        zonaAsignada = "z9"

        val seleccion = crearSeleccion()
        advanceUntilIdle()

        assertTrue(seleccion.uiState.value.zonaAsignadaInactiva)
        assertEquals("z1", seleccion.uiState.value.zonaId)
        assertTrue(seleccion.uiState.value.puedeContinuar)
    }

    // --- Carga de la selección y navegación ---------------------------------------------------

    @Test
    fun `las zonas cargan aunque la consulta de zona asignada no responda`() = runTest(dispatcher) {
        iniciarSesion()
        zonaAsignadaNuncaResponde = true

        val seleccion = crearSeleccion()
        advanceUntilIdle()

        val estado = seleccion.uiState.value
        assertFalse(estado.cargandoCatalogo, "antes las zonas esperaban a zonaAsignada y la lista quedaba vacía")
        assertEquals(2, estado.zonas.size)
        assertTrue(estado.puedeContinuar)
    }

    @Test
    fun `la zona asignada se preselecciona pero no pisa una eleccion manual`() = runTest(dispatcher) {
        iniciarSesion()
        zonaAsignada = "z2"
        val seleccion = crearSeleccion()
        advanceUntilIdle()
        assertEquals("z2", seleccion.uiState.value.zonaId)

        seleccion.seleccionarZona("z1")
        zonasActivas.value = zonasActivas.value + Zona("z3", "PLANTA", true)
        advanceUntilIdle()

        assertEquals("z1", seleccion.uiState.value.zonaId)
    }

    @Test
    fun `sin jornada del dia reabrir la app va a la seleccion y una nueva seleccion no navega sola`() = runTest(dispatcher) {
        iniciarSesion()
        assertIs<InicioAcopiador.SeleccionarZonaVehiculo>(resolver("u1"))
        assertEquals(Pantalla.AcopiadorSeleccionZonaVehiculo, reabrirApp())

        val seleccion = crearSeleccion()
        advanceUntilIdle()

        assertFalse(seleccion.uiState.value.jornadaAbierta, "sin apertura en esta sesión no hay éxito que navegar")
    }

    @Test
    fun `tras reabrir la app con la jornada cerrada un intento de abrir no genera navegacion falsa`() = runTest(dispatcher) {
        iniciarSesion()
        val primera = crearSeleccion()
        advanceUntilIdle()
        primera.abrirJornada()
        advanceUntilIdle()
        assertTrue(primera.uiState.value.jornadaAbierta)
        cerrarJornada(enCurso.observar().first()!!.id).getOrThrow()
        store.clear() // proceso nuevo: los ViewModels de la sesión anterior no sobreviven

        assertEquals(Pantalla.AcopiadorHome, reabrirApp(), "va al resumen terminado, no a la selección")
        val segunda = crearSeleccion()
        advanceUntilIdle()
        segunda.abrirJornada()
        advanceUntilIdle()

        assertFalse(segunda.uiState.value.jornadaAbierta)
        assertNotNull(segunda.uiState.value.error)
        assertEquals(1, jornadas.observarTodas().first().size)
    }

    @Test
    fun `el aviso de cerrar sesion explica jornada abierta y pendientes`() {
        val texto = textoConfirmacionCierreSesion(jornadaAbierta = true, pendientesSync = 2)
        assertTrue(texto.contains("seguirá abierta"))
        assertFalse(texto.contains("ubicación"), "el seguimiento GPS ya no existe: el aviso no debe mencionarlo")
        assertTrue(texto.contains("2 registros sin sincronizar"))
        assertTrue(texto.contains("no se borran"))
    }

    private suspend fun AbrirJornadaUseCase.getOrThrowAbrir() = this("u1", "z1", "v1").getOrThrow()
}
