package pe.ecolecta.presentation

import androidx.lifecycle.ViewModelStore
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.test.*
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.fake.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.presentation.proveedor.PortalProveedorViewModel
import kotlin.test.*

@OptIn(kotlinx.coroutines.ExperimentalCoroutinesApi::class)
class PortalProveedorViewModelTest {
    private val dispatcher = StandardTestDispatcher()
    private val store = ViewModelStore()
    private val sesiones = InMemorySesionRepository()
    private val proveedores = FakeProveedorRepository()
    private val entregas = FakeEntregaRepository()
    private val solicitudes = MutableStateFlow(emptyList<SolicitudProveedor>())
    private var fallo = false
    private val portal = object : PortalProveedorRepository {
        // Devuelve deliberadamente todas las filas para comprobar defensa por propiedad en la presentación.
        override fun solicitudes(proveedorId: String) = solicitudes
        override fun pagos(proveedorId: String) = flowOf(emptyList<PagoProveedor>())
        override suspend fun guardar(solicitud: SolicitudProveedor) {
            if(fallo) error("Disco no disponible")
            solicitudes.value += solicitud
        }
    }
    @BeforeTest fun preparar() { Dispatchers.setMain(dispatcher) }
    @AfterTest fun limpiar() { store.clear(); Dispatchers.resetMain() }

    private suspend fun crear(rol: Rol = Rol.PROVEEDOR, vinculado: Boolean = true): PortalProveedorViewModel {
        val usuario = Usuario("u1", "proveedor", "Rosa", "12345678", "", "", true, listOf(rol), 0)
        sesiones.iniciar(Sesion(usuario, rol))
        if(vinculado) proveedores.insertar(Proveedor.crear("p1", "P1", "Rosa", "12345678", null, null, "z1", updatedAt = 0, usuarioId = "u1").getOrThrow())
        entregas.sembrar(Entrega.crear("e1", "j", "p1", "a", "z1", "v", 10.0, 1, null, 0, "d", null).getOrThrow())
        val calidad = object : ControlCalidadRepository {
            override fun observarTodos() = flowOf(emptyList<ControlCalidad>())
            override fun observarPorUsuario(usuarioId: String) = observarTodos()
            override suspend fun obtenerPorId(id: String): ControlCalidad? = null
            override suspend fun existeCodigoMuestra(codigo: String) = false
            override suspend fun insertar(control: ControlCalidad) = Unit
            override suspend fun obtenerZonaAsignada(usuarioId: String): String? = null
        }
        val zonas = object : ZonaRepository {
            override fun observarTodas() = flowOf(listOf(Zona("z1", "Origen", true), Zona("z2", "Destino", true), Zona("z3", "Inactiva", false)))
            override fun observarActivas() = observarTodas().map { it.filter { zona -> zona.activo } }
            override suspend fun obtenerPorId(id: String) = observarTodas().first().find { it.id == id }
            override suspend fun insertar(zona: Zona) = Unit
            override suspend fun actualizar(zona: Zona) = Unit
            override suspend fun desactivar(id: String) = Unit
            override suspend fun contarProveedoresEnZona(id: String) = 0L
        }
        val rutas = object : RutaProveedorCacheRepository {
            override suspend fun guardar(usuarioId: String, ubicacion: UbicacionAcopiador) = Unit
            override suspend fun obtener(usuarioId: String): UbicacionAcopiador? = null
            override suspend fun eliminar(usuarioId: String) = Unit
        }
        return PortalProveedorViewModel(sesiones, ObtenerPerfilProveedorUseCase(proveedores), entregas, calidad, zonas,
            FakeUsuarioRepository(), rutas, portal, FakeReloj()).also { store.put("portal", it) }
    }

    @Test fun usuarioSinProveedorTerminaCargaConError() = runTest(dispatcher) {
        val vm = crear(vinculado = false)
        advanceUntilIdle()
        assertFalse(vm.state.value.cargando)
        assertNotNull(vm.state.value.error)
        assertNull(vm.state.value.proveedor)
    }

    @Test fun rolAjenoNoPuedeCargarNiGuardar() = runTest(dispatcher) {
        val vm = crear(Rol.ACOPIADOR)
        advanceUntilIdle()
        assertNull(vm.state.value.proveedor)
        vm.reclamar("e1", "12", "Litros mal registrados", "La medida fue incorrecta", null)
        advanceUntilIdle()
        assertTrue(solicitudes.value.isEmpty())
    }

    @Test fun rechazaEntregaAjenaYEvitaDuplicadosSinModificarLitros() = runTest(dispatcher) {
        val vm = crear()
        advanceUntilIdle()
        vm.reclamar("ajena", "12", "Litros mal registrados", "La medida fue incorrecta", null)
        advanceUntilIdle()
        assertTrue(solicitudes.value.isEmpty())
        vm.reclamar("e1", "12", "Litros mal registrados", "La medida fue incorrecta", null)
        advanceUntilIdle()
        assertEquals("PENDIENTE_ENVIO", solicitudes.value.single().estado)
        vm.reclamar("e1", "12", "Litros mal registrados", "La medida fue incorrecta", null)
        advanceUntilIdle()
        assertEquals(1, solicitudes.value.size)
        assertNotNull(vm.state.value.error)
        assertEquals(10.0, entregas.obtenerPorId("e1")!!.litros)
    }

    @Test fun trasladoValidaZonaYNoCambiaAsignacion() = runTest(dispatcher) {
        val vm = crear()
        advanceUntilIdle()
        for(zona in listOf("z1", "z3", "inexistente")) {
            vm.trasladar(zona, "Me mudé a otra localidad")
            advanceUntilIdle()
            assertTrue(solicitudes.value.isEmpty())
        }
        vm.trasladar("z2", "Me mudé a otra localidad")
        advanceUntilIdle()
        assertEquals("z1", proveedores.obtenerPorId("p1")!!.zonaId)
        assertEquals("z2", solicitudes.value.single().referenciaId)
        vm.trasladar("z2", "Otra solicitud duplicada")
        advanceUntilIdle()
        assertEquals(1, solicitudes.value.size)
    }

    @Test fun errorDeDiscoNoAnunciaExitoYPermiteReintentar() = runTest(dispatcher) {
        val vm = crear()
        advanceUntilIdle()
        fallo = true
        vm.reclamar(null, "12", "Entrega no registrada", "Falta la entrega de ayer", null)
        advanceUntilIdle()
        assertNull(vm.state.value.confirmacion)
        assertFalse(vm.state.value.guardando)
        assertNotNull(vm.state.value.error)
        fallo = false
        vm.reclamar(null, "12", "Entrega no registrada", "Falta la entrega de ayer", null)
        advanceUntilIdle()
        assertEquals(1, solicitudes.value.size)
    }

    @Test fun noMuestraSolicitudesDeOtroProveedorNiGuardaTrasCerrarSesion() = runTest(dispatcher) {
        solicitudes.value = listOf(SolicitudProveedor("ajena", "p2", "RECLAMO", null, 5.0, "Otro motivo", "Privado", creadaEn = 0))
        val vm = crear()
        advanceUntilIdle()
        assertTrue(vm.state.value.solicitudes.isEmpty())
        sesiones.cerrar()
        vm.reclamar(null, "12", "Entrega no registrada", "Falta la entrega de ayer", null)
        advanceUntilIdle()
        assertEquals(1, solicitudes.value.size)
        assertNotNull(vm.state.value.error)
    }
}
