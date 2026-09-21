package pe.ecolecta.presentation.calidad

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.test.*
import kotlinx.serialization.encodeToString
import kotlinx.serialization.json.Json
import kotlin.test.*
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.fake.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

@OptIn(kotlinx.coroutines.ExperimentalCoroutinesApi::class)
class CalidadVisitasTest {
    private val dispatcher = StandardTestDispatcher()
    @BeforeTest fun preparar() { Dispatchers.setMain(dispatcher) }
    @AfterTest fun limpiar() { Dispatchers.resetMain() }

    private suspend fun crear(): Pair<CalidadViewModel, MemoriaCalidad> {
        val proveedores = FakeProveedorRepository()
        listOf("a", "b").forEach { zona ->
            proveedores.insertar(Proveedor("p-$zona", "PRV-$zona", "Finca $zona", "12345678", null, null,
                zona, 1, 40.0, EstadoProveedor.ACTIVO, 0, SyncState.SYNCED, dueno = "Rosa Mamani"))
        }
        val sesiones = InMemorySesionRepository()
        sesiones.iniciar(Sesion(Usuario("tecnico", "calidad", "Miguel Vargas", "00000000", "", "", true, listOf(Rol.CALIDAD), 0), Rol.CALIDAD))
        val zonas = object: ZonaRepository {
            override fun observarTodas() = flowOf(listOf(Zona("a", "Zona A", true), Zona("b", "Zona B", true)))
            override fun observarActivas() = observarTodas()
            override suspend fun obtenerPorId(id: String) = Zona(id, id, true)
            override suspend fun insertar(zona: Zona) {}
            override suspend fun actualizar(zona: Zona) {}
            override suspend fun desactivar(id: String) {}
            override suspend fun contarProveedoresEnZona(id: String) = 1L
        }
        val repo = MemoriaCalidad()
        return CalidadViewModel(repo, ListarProveedoresUseCase(proveedores), ObtenerSesionUseCase(sesiones),
            FakeReloj(), ListarZonasUseCase(zonas)) to repo
    }

    private fun llenarValoresAprobados(vm: CalidadViewModel) {
        vm.campo("temperatura", "6.0"); vm.campo("grasa", "3.5"); vm.campo("sng", "8.7")
        vm.campo("densidad", "1.030"); vm.campo("proteina", "3.2"); vm.campo("lactosa", "4.7")
        vm.campo("sales", "0.70"); vm.campo("solidos", "12.2"); vm.campo("agua", "0.0")
        vm.campo("congelacion", "-0.530"); vm.campo("ph", "6.7")
    }

    @Test fun cambiarZonaMuestraSoloSusProveedoresYAbreElFormulario() = runTest(dispatcher) {
        val (vm, _) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a")
        assertEquals(listOf("p-a"), vm.uiState.value.proveedoresFiltrados.map { it.id })
        vm.zona("b")
        assertEquals(listOf("p-b"), vm.uiState.value.proveedoresFiltrados.map { it.id })
        vm.proveedor("p-b")
        assertEquals(PasoCalidad.FORMULARIO, vm.uiState.value.paso)
        assertEquals("p-b", vm.uiState.value.borrador.proveedorId)
    }

    @Test fun sinValoresNoSePuedeGuardarPeroFechaYHoraVienenPorDefecto() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        assertTrue(vm.uiState.value.borrador.fecha.isNotBlank())
        assertTrue(vm.uiState.value.borrador.hora.isNotBlank())
        vm.guardar(); advanceUntilIdle()
        assertTrue(repo.lista.value.isEmpty())
        assertNotNull(vm.uiState.value.error)
    }

    @Test fun guardaConSalesYQuedaVinculadoAProveedorZonaYTecnico() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("b"); vm.proveedor("p-b")
        llenarValoresAprobados(vm)
        vm.guardar(); advanceUntilIdle()
        val guardado = repo.lista.value.single()
        assertEquals(PasoCalidad.GUARDADO, vm.uiState.value.paso)
        assertEquals("p-b", guardado.proveedorId)
        assertEquals("b", guardado.visita.zonaId)
        assertEquals("Miguel Vargas", guardado.visita.tecnicoNombre)
        assertEquals(0.70, guardado.sales)
        assertEquals(EstadoControlCalidad.APROBADO, guardado.estado)
    }

    @Test fun valorFueraDeReferenciaSeGuardaYQuedaObservadoORechazado() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        llenarValoresAprobados(vm)
        vm.campo("agua", "2.0")
        vm.guardar(); advanceUntilIdle()
        val guardado = repo.lista.value.single()
        assertEquals(EstadoControlCalidad.RECHAZADO, guardado.estado)
        assertEquals(2.0, guardado.aguaAnadida)
    }

    @Test fun formatoInvalidoBloqueaElGuardadoPeroFueraDeRangoNo() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        llenarValoresAprobados(vm)
        vm.campo("grasa", "no-es-un-numero")
        vm.guardar(); advanceUntilIdle()
        assertTrue(repo.lista.value.isEmpty())
        assertNotNull(vm.uiState.value.error)
        vm.campo("grasa", "-3.5")
        vm.guardar(); advanceUntilIdle()
        assertTrue(repo.lista.value.isEmpty(), "un valor negativo en un campo que no lo admite debe bloquear el guardado")
        vm.campo("grasa", "50")
        vm.guardar(); advanceUntilIdle()
        assertEquals(1, repo.lista.value.size, "fuera de referencia no bloquea; solo queda observado")
    }

    @Test fun puntoDeCongelacionEnHNoSeConfundeConCYQuedaObservado() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        llenarValoresAprobados(vm)
        vm.campo("unidad", "°H")
        assertFalse(vm.uiState.value.borrador.correcto("congelacion"))
        vm.guardar(); advanceUntilIdle()
        val guardado = repo.lista.value.single()
        assertEquals(EstadoControlCalidad.OBSERVADO, guardado.estado)
        assertEquals("°H", guardado.visita.unidadCongelacion)
    }

    @Test fun escanearConDatosPreviosPideConfirmacionAntesDeReemplazar() = runTest(dispatcher) {
        val (vm, _) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        vm.campo("grasa", "3.5")
        val comprobante = """
            SN: 49731  Mode: 1
            Temp. ........ 6,5 C
            Grasa ........ 9.99%
            Sales ........ 0.70%
            pH ........... 6.7
        """.trimIndent()
        vm.escanear(comprobante)
        assertNotNull(vm.uiState.value.escaneoPendiente)
        assertEquals("3.5", vm.uiState.value.borrador.valores["grasa"])
        vm.descartarEscaneo()
        assertNull(vm.uiState.value.escaneoPendiente)
        assertEquals("3.5", vm.uiState.value.borrador.valores["grasa"], "descartar el escaneo conserva lo escrito a mano")
        vm.escanear(comprobante)
        vm.confirmarEscaneo()
        assertEquals("9.99", vm.uiState.value.borrador.valores["grasa"])
        assertEquals("49731", vm.uiState.value.borrador.serial)
        assertEquals(OrigenCaptura.ESCANER, vm.uiState.value.borrador.origen)
    }

    @Test fun escaneoSinDatosPreviosSeAplicaDeInmediatoYDejaVaciosLosNoReconocidos() = runTest(dispatcher) {
        val (vm, _) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        val comprobante = "Grasa ........ 3.45%\nAgua anadida . 0.0%"
        vm.escanear(comprobante)
        assertNull(vm.uiState.value.escaneoPendiente)
        assertEquals("3.45", vm.uiState.value.borrador.valores["grasa"])
        assertEquals("0.0", vm.uiState.value.borrador.valores["agua"], "un cero real reconocido no debe quedar en blanco")
        assertTrue(vm.uiState.value.borrador.valores["ph"].isNullOrBlank(), "lo no reconocido queda vacío, nunca en cero")
    }

    @Test fun cambiarProveedorConDatosCapturadosPideConfirmacion() = runTest(dispatcher) {
        val (vm, _) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        vm.campo("grasa", "3.5")
        vm.cambiarProveedor()
        assertTrue(vm.uiState.value.confirmarCambioProveedor)
        assertEquals(PasoCalidad.FORMULARIO, vm.uiState.value.paso)
        vm.confirmarCambioProveedor()
        assertEquals(PasoCalidad.SELECCION, vm.uiState.value.paso)
        assertEquals("", vm.uiState.value.borrador.proveedorId)
        assertTrue(vm.uiState.value.borrador.valores.isEmpty())
    }

    @Test fun errorDeGuardadoConservaBorradorYPermiteReintentar() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        llenarValoresAprobados(vm)
        repo.fallar = true; vm.guardar(); advanceUntilIdle()
        assertFalse(vm.uiState.value.guardando)
        assertEquals(PasoCalidad.FORMULARIO, vm.uiState.value.paso)
        assertNotNull(vm.uiState.value.error)
        assertEquals("3.5", vm.uiState.value.borrador.valores["grasa"], "los datos siguen en el formulario tras el error")
        repo.fallar = false; vm.guardar(); advanceUntilIdle()
        assertEquals(1, repo.lista.value.size)
    }

    @Test fun dobleToqueNoDuplicaElRegistro() = runTest(dispatcher) {
        val (vm, repo) = crear(); advanceUntilIdle()
        vm.nuevo(); vm.zona("a"); vm.proveedor("p-a")
        llenarValoresAprobados(vm)
        vm.guardar(); vm.guardar(); advanceUntilIdle()
        assertEquals(1, repo.lista.value.size)
    }

    @Test fun filasAntiguasSinMetadataSiguenSiendoLegibles() {
        val v = Json.decodeFromString<DatosVisitaCalidad>("{}")
        assertNull(v.confirmadaEn)
        assertEquals("", v.accionTomada)
        assertEquals(v, Json.decodeFromString<DatosVisitaCalidad>(Json.encodeToString(v)))
    }
}

private class MemoriaCalidad: ControlCalidadRepository {
    val lista = MutableStateFlow<List<ControlCalidad>>(emptyList())
    var fallar = false
    override fun observarTodos() = lista
    override fun observarPorUsuario(usuarioId: String) = lista.map { it.filter { c -> c.usuarioId == usuarioId } }
    override suspend fun obtenerPorId(id: String) = lista.value.firstOrNull { it.id == id }
    override suspend fun existeCodigoMuestra(codigo: String) = lista.value.any { it.codigoMuestra == codigo }
    override suspend fun insertar(control: ControlCalidad) {
        if(fallar) throw IllegalStateException("Almacenamiento no disponible")
        lista.value += control
    }
    override suspend fun obtenerZonaAsignada(usuarioId: String) = "a"
}
