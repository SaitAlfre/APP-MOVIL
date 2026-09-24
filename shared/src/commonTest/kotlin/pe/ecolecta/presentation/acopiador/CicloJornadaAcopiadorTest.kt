package pe.ecolecta.presentation.acopiador

import androidx.lifecycle.ViewModelStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ReanudarJornadaSiExisteUseCase
import pe.ecolecta.domain.usecase.vehiculo.ListarVehiculosUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.acopiador.onboarding.SeleccionZonaVehiculoViewModel
import kotlin.test.AfterTest
import kotlin.test.BeforeTest
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNotEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Regresión del ciclo completo abrir → registrar entrega → cerrar → volver a "Abrir jornada" con el
 * [SeleccionZonaVehiculoViewModel] que, como en la app, sobrevive toda la sesión. Antes del arreglo su
 * `jornadaAbierta` seguía en `true` tras el cierre (la pantalla rebotaba al inicio) y reabrir el mismo
 * día devolvía la jornada cerrada como si fuera un éxito.
 */
@OptIn(kotlinx.coroutines.ExperimentalCoroutinesApi::class)
class CicloJornadaAcopiadorTest {
    private val dispatcher = StandardTestDispatcher()
    private val store = ViewModelStore()
    private val sesiones = InMemorySesionRepository()
    private val jornadas = FakeJornadaRepository()
    private val jornadaEnCurso = InMemoryJornadaEnCursoRepository()
    private val entregas = FakeEntregaRepository()
    private val reloj = FakeReloj()

    private val zonas = object : ZonaRepository {
        override fun observarTodas() = flowOf(listOf(Zona("z1", "Huata Norte", true)))
        override fun observarActivas() = observarTodas()
        override suspend fun obtenerPorId(id: String): Zona? = null
        override suspend fun insertar(zona: Zona) = Unit
        override suspend fun actualizar(zona: Zona) = Unit
        override suspend fun desactivar(id: String) = Unit
        override suspend fun contarProveedoresEnZona(id: String) = 0L
    }
    private val vehiculos = object : VehiculoRepository {
        override fun observarTodos() = flowOf(listOf(Vehiculo("v1", "Moto", "ABC-123", true)))
        override fun observarActivos() = observarTodos()
        override suspend fun obtenerPorId(id: String): Vehiculo? = null
        override suspend fun existePlaca(placa: String, idExcluido: String) = false
        override suspend fun insertar(vehiculo: Vehiculo) = Unit
        override suspend fun actualizar(vehiculo: Vehiculo) = Unit
        override suspend fun desactivar(id: String) = Unit
    }
    private val cuentas = object : CuentasRepository {
        override fun observarZonasAsignadas() = flowOf(emptyMap<String, String>())
        override suspend fun zonaAsignada(usuarioId: String): String? = null
        override suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?) = Unit
        override suspend fun existeNombreZona(nombre: String, idExcluido: String) = false
        override suspend fun vehiculoEnJornadaAbierta(vehiculoId: String) = false
        override suspend fun crearCuenta(usuario: pe.ecolecta.domain.model.Usuario, zonaId: String?, fichaNueva: pe.ecolecta.domain.model.Proveedor?, fichaExistenteId: String?, auditorias: List<pe.ecolecta.domain.model.Auditoria>) = Unit
    }

    private val cerrarJornada = CerrarJornadaUseCase(
        jornadas,
        jornadaEnCurso,
        reloj,
        FakeAuditoriaRepository(),
        FakeDeviceIdProvider(),
    )
    private val cerrarSesion = CerrarSesionUseCase(sesiones)

    @BeforeTest fun preparar() { Dispatchers.setMain(dispatcher) }
    @AfterTest fun limpiar() { store.clear(); Dispatchers.resetMain() }

    private suspend fun iniciarSesion() {
        val usuario = Usuario("u1", "acopiador", "Ana", "12345678", "", "", true, listOf(Rol.ACOPIADOR), 0)
        sesiones.iniciar(Sesion(usuario, Rol.ACOPIADOR))
    }

    private fun crearSeleccion(relojVm: Reloj = reloj, clave: String = "seleccion") = SeleccionZonaVehiculoViewModel(
        ListarZonasUseCase(zonas),
        ListarVehiculosUseCase(vehiculos),
        AbrirJornadaUseCase(jornadas, jornadaEnCurso, relojVm),
        ObtenerSesionUseCase(sesiones),
        relojVm,
        cuentas,
        cerrarSesion,
    ).also { store.put(clave, it) }

    /** Lo que hace SeleccionZonaVehiculoScreen: navegar una sola vez por cada éxito y consumirlo. */
    private fun navegarSiAbrio(vm: SeleccionZonaVehiculoViewModel): Boolean {
        if (!vm.uiState.value.jornadaAbierta) return false
        vm.navegacionAJornadaAtendida()
        return true
    }

    @Test
    fun `tras abrir y cerrar volver a la seleccion no reutiliza el exito anterior`() = runTest(dispatcher) {
        iniciarSesion()
        val vm = crearSeleccion()
        advanceUntilIdle()

        vm.abrirJornada()
        advanceUntilIdle()
        assertTrue(navegarSiAbrio(vm), "el primer éxito debe navegar al inicio")
        val jornada = jornadaEnCurso.observar().first()
        assertNotNull(jornada)
        entregas.sembrar(Entrega.crear("e1", jornada.id, "p1", "u1", "z1", "v1", 25.0, 2, null, 0, "d", null).getOrThrow())

        cerrarJornada(jornada.id).getOrThrow()

        // Mismo ViewModel (misma sesión) al volver a "Abrir jornada": no debe rebotar al inicio.
        assertFalse(vm.uiState.value.jornadaAbierta, "el éxito ya atendido no debe volver a disparar la navegación")
        assertFalse(navegarSiAbrio(vm))

        vm.abrirJornada()
        advanceUntilIdle()
        assertFalse(navegarSiAbrio(vm), "reabrir el mismo día no es una apertura exitosa")
        assertEquals(
            "Ya cerraste tu jornada de hoy. Solo se permite una jornada por día: podrás abrir una nueva mañana.",
            vm.uiState.value.error,
        )
        assertFalse(vm.uiState.value.cargando)
        assertNull(jornadaEnCurso.observar().first(), "una jornada cerrada no debe quedar como jornada en curso")
        assertFalse(jornadas.obtenerPorId(jornada.id)!!.estaAbierta, "el cierre persistido se conserva")
        assertEquals(1, entregas.observarPorJornada(jornada.id).first().size, "las entregas de la jornada se conservan")
    }

    @Test
    fun `cerrar sesion y volver a entrar no recupera la jornada cerrada como activa`() = runTest(dispatcher) {
        iniciarSesion()
        val vm = crearSeleccion()
        advanceUntilIdle()
        vm.abrirJornada()
        advanceUntilIdle()
        val jornada = jornadaEnCurso.observar().first()!!
        cerrarJornada(jornada.id).getOrThrow()

        sesiones.cerrar()
        iniciarSesion()
        val reanudada = ReanudarJornadaSiExisteUseCase(jornadas, jornadaEnCurso)("u1")

        assertNull(reanudada, "App.kt debe mandar a la selección, no al inicio con una jornada cerrada")
        assertNull(jornadaEnCurso.observar().first())
    }

    @Test
    fun `al dia siguiente la misma sesion abre una jornada nueva y navega una vez`() = runTest(dispatcher) {
        iniciarSesion()
        val hoy = crearSeleccion()
        advanceUntilIdle()
        hoy.abrirJornada()
        advanceUntilIdle()
        navegarSiAbrio(hoy)
        val ayer = jornadaEnCurso.observar().first()!!
        cerrarJornada(ayer.id).getOrThrow()

        val manana = crearSeleccion(FakeReloj(fecha = LocalDate(2026, 1, 16)), clave = "manana")
        advanceUntilIdle()
        manana.abrirJornada()
        advanceUntilIdle()

        assertTrue(navegarSiAbrio(manana))
        assertFalse(navegarSiAbrio(manana), "el mismo éxito no debe navegar dos veces")
        val nueva = jornadaEnCurso.observar().first()!!
        assertNotEquals(ayer.id, nueva.id)
        assertTrue(nueva.estaAbierta)
        assertEquals(2, jornadas.observarTodas().first().size, "se conserva el historial")
    }
}
