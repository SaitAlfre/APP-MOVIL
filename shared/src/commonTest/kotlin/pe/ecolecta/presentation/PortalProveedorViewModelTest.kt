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
    private val pagos = MutableStateFlow(emptyList<PagoProveedor>())
    private val panel = FakeServidorWeb()
    private val gestion = object : GestionPortalRepository {
        override fun todasSolicitudes() = solicitudes
        override fun todosPagos() = pagos
        override suspend fun actualizarSolicitud(solicitud: SolicitudProveedor) = Unit
        override suspend fun guardarPagos(pagos: List<PagoProveedor>, en: Long) {
            val ids = pagos.map { it.id }.toSet()
            this@PortalProveedorViewModelTest.pagos.value = this@PortalProveedorViewModelTest.pagos.value.filterNot { it.id in ids } + pagos
        }
    }
    private var fallo = false
    private val recibidos = FakeRegistroRecibidoRepository()
    private val remoto = FakeRegistroAcopioRemoto()
    private var remotoForzado: RegistroAcopioRemotoRepository? = null
    private val portal = object : PortalProveedorRepository {
        // Devuelve deliberadamente todas las filas para comprobar defensa por propiedad en la presentación.
        override fun solicitudes(proveedorId: String) = solicitudes
        // Igual que la base local: todas las filas PAGO (la presentación filtra por su ficha).
        override fun pagos(proveedorId: String) = pagos
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
        return PortalProveedorViewModel(sesiones, ObtenerPerfilProveedorUseCase(proveedores), entregas, calidad, zonas,
            FakeUsuarioRepository(), portal, FakeReloj(), FakeSinRecojoRepository(), recibidos, remotoForzado ?: remoto, panel, gestion).also { store.put("portal", it) }
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

    // ---- Lista de acopio compartida ("Mi ciclo") ----

    private fun doc(id: String, codigo: String, litros: Double, tipo: pe.ecolecta.domain.acopio.TipoRegistroCompartido = pe.ecolecta.domain.acopio.TipoRegistroCompartido.ENTREGA) =
        pe.ecolecta.domain.acopio.RegistroAcopioCompartido(
            id = id, tipo = tipo, proveedorCodigo = codigo, zonaId = "z1", jornadaId = "j", acopiadorId = "acop-remoto",
            acopiadorNombre = "Juan Pérez", fecha = "2023-11-14", registradoEn = FakeReloj().ahora().toEpochMilliseconds(),
            litros = litros, tachos = 1, actualizadoEn = 1,
        )

    @Test fun recibeLoSincronizadoYNuncaDatosDeOtroProveedor() = runTest(dispatcher) {
        // Servidor "mal configurado" que devolviera documentos ajenos: el portal los descarta igual.
        val todos = listOf(doc("r1", "P1", 12.5), doc("r2", "P2", 80.0), doc("r3", "P3", 7.0))
        remotoForzado = object : RegistroAcopioRemotoRepository {
            override val configurado = true
            override suspend fun publicar(registro: pe.ecolecta.domain.acopio.RegistroAcopioCompartido) = Result.success(Unit)
            override fun observarDeProveedor(proveedorCodigo: String) = flowOf(EventoRegistrosRemotos.Recibidos(todos))
        }
        val vm = crear()
        advanceUntilIdle()
        val s = vm.state.value
        assertEquals(pe.ecolecta.presentation.proveedor.ConexionPortal.EN_LINEA, s.conexion)
        assertEquals(setOf("e1", "r1"), s.recojos.map { it.id }.toSet(), "su entrega local y la recibida; nunca r2/r3")
        assertTrue(s.entregas.none { it.id == "r2" || it.id == "r3" })
        assertEquals(12.5, s.diaHoy!!.totalLitros)
        assertEquals(pe.ecolecta.domain.acopio.EstadoSincronizacion.SINCRONIZADO, s.diaHoy!!.sincronizacion)
        assertEquals(setOf("P1"), recibidos.filas.value.values.map { it.first.proveedorCodigo }.toSet(), "la copia local solo guarda lo propio")
        assertEquals("Juan Pérez", s.usuarios["acop-remoto"])
    }

    @Test fun sinConexionMuestraEstadoHonestoYLoUltimoRecibido() = runTest(dispatcher) {
        recibidos.guardar(listOf(doc("r1", "P1", 9.0)), recibidoEn = 5)
        remoto.enLinea.value = false
        val vm = crear()
        advanceUntilIdle()
        assertEquals(pe.ecolecta.presentation.proveedor.ConexionPortal.SIN_CONEXION, vm.state.value.conexion)
        assertEquals(9.0, vm.state.value.diaHoy!!.totalLitros, "se muestra la última copia recibida")
        assertEquals(5L, vm.state.value.ultimaRecepcion)
    }

    @Test fun unaEntregaLocalPendienteNoSePresentaComoConfirmada() = runTest(dispatcher) {
        remotoForzado = FakeRegistroAcopioRemoto(configurado = false)
        entregas.sembrar(Entrega.crear("local", "j", "p1", "a", "z1", "v", 20.0, 1, null, FakeReloj().ahora().toEpochMilliseconds(), "d", null).getOrThrow())
        val vm = crear()
        advanceUntilIdle()
        val dia = vm.state.value.diaHoy!!
        assertEquals(pe.ecolecta.domain.acopio.EstadoSincronizacion.EN_ESTE_CELULAR, dia.sincronizacion)
        assertTrue(pe.ecolecta.presentation.proveedor.textoDiaProveedor(dia).startsWith("Por confirmar"))
        assertEquals(pe.ecolecta.presentation.proveedor.ConexionPortal.NO_CONFIGURADA, vm.state.value.conexion)
    }

    @Test fun elCicloDelProveedorTieneLasMismasSeisFechasQueElAcopiador() = runTest(dispatcher) {
        val vm = crear()
        advanceUntilIdle()
        val s = vm.state.value
        assertEquals(pe.ecolecta.domain.acopio.cicloAcopioDe(s.hoy, "cualquiera").dias, s.diasCiclo.map { it.fecha })
        assertEquals(6, s.diasCiclo.size)
    }

    private fun liquidacion(id: String, desde: String, hasta: String, estado: String, proveedorId: String = "") =
        PagoProveedor(id, proveedorId, desde, hasta, 100.0, 1.6, 160.0, 10.0, 150.0, estado, if (estado == "PAGADA") "2026-09-18" else null)

    @Test fun inicioYMisPagosMuestranLaMismaLiquidacionPublicadaDelPanel() = runTest(dispatcher) {
        panel.sesiones.value = setOf("u1")
        panel.liquidaciones["u1"] = listOf(
            liquidacion("${PREFIJO_PAGO_SERVIDOR}7", "2026-09-10", "2026-09-16", "PAGADA"),
            liquidacion("${PREFIJO_PAGO_SERVIDOR}9", "2026-09-03", "2026-09-09", "PENDIENTE"),
        )
        val vm = crear()
        advanceUntilIdle()

        val s = vm.state.value
        assertEquals(listOf("PAGADA", "PENDIENTE"), s.pagos.map { it.estado }, "Mis pagos: estado real, más reciente primero")
        val pago = s.pagos.first()
        assertEquals("${PREFIJO_PAGO_SERVIDOR}7", pago.id)
        assertEquals("p1", pago.proveedorId, "se guarda con la ficha local de la sesión")
        assertEquals(pago, s.ultimaLiquidacion, "Inicio resume exactamente lo que muestra Mis pagos")
        assertEquals(1.6, s.ultimaLiquidacion!!.precio)
        assertEquals(150.0, s.ultimaLiquidacion!!.total)
        assertNull(s.avisoPagos)
    }

    @Test fun sinSesionConElPanelNoInventaLiquidacionesYLoDice() = runTest(dispatcher) {
        val vm = crear()
        advanceUntilIdle()

        assertTrue(vm.state.value.pagos.isEmpty())
        assertNull(vm.state.value.ultimaLiquidacion)
        assertNotNull(vm.state.value.avisoPagos)

        // Al enlazarse la cuenta (en segundo plano tras el login) llegan las liquidaciones.
        panel.liquidaciones["u1"] = listOf(liquidacion("${PREFIJO_PAGO_SERVIDOR}8", "2026-09-10", "2026-09-16", "PENDIENTE"))
        panel.sesiones.value = setOf("u1")
        advanceUntilIdle()
        assertEquals("PENDIENTE", vm.state.value.ultimaLiquidacion!!.estado)
        assertNull(vm.state.value.ultimaLiquidacion!!.fechaPago)
    }

    @Test fun sinConexionConservaLasUltimasRecibidasConAvisoHonesto() = runTest(dispatcher) {
        pagos.value = listOf(liquidacion("${PREFIJO_PAGO_SERVIDOR}5", "2026-09-03", "2026-09-09", "PAGADA", proveedorId = "p1"))
        panel.sesiones.value = setOf("u1")
        panel.enLinea.value = false
        val vm = crear()
        advanceUntilIdle()

        assertEquals("${PREFIJO_PAGO_SERVIDOR}5", vm.state.value.ultimaLiquidacion!!.id)
        assertTrue(vm.state.value.avisoPagos!!.contains("Sin conexión"))
    }

    @Test fun noMuestraLiquidacionesDeOtroProveedorAunqueEstenEnElCelular() = runTest(dispatcher) {
        pagos.value = listOf(liquidacion("liq-x", "2026-09-10", "2026-09-16", "PAGADA", proveedorId = "otro"))
        val vm = crear()
        advanceUntilIdle()

        assertTrue(vm.state.value.pagos.isEmpty())
        assertNull(vm.state.value.ultimaLiquidacion)
    }
}
