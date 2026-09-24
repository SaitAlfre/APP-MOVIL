package pe.ecolecta.presentation.calidad

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.*
import kotlinx.coroutines.test.*
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.auth.LoginUiEvent
import pe.ecolecta.presentation.auth.LoginViewModel
import kotlin.test.*

/**
 * Reproduce exactamente lo que hace la app al iniciar sesión con las credenciales de prueba de
 * `calidad_faon` (documentadas en CREDENCIALES_PRUEBA_MOVIL.md) y verifica que el rol Técnico de
 * calidad cargue después, usando los mismos casos de uso reales que usa la UI — no atajos.
 */
@OptIn(kotlinx.coroutines.ExperimentalCoroutinesApi::class)
class LoginCalidadFaonTest {
    private val dispatcher = StandardTestDispatcher()
    @BeforeTest fun preparar() { Dispatchers.setMain(dispatcher) }
    @AfterTest fun limpiar() { Dispatchers.resetMain() }

    @Test
    fun `calidad_faon con PIN 8642 inicia sesion y carga el panel de Tecnico de calidad`() = runTest(dispatcher) {
        val pinHasher = Pbkdf2PinHasher()
        val usuarios = FakeUsuarioRepository()
        val auditoria = FakeAuditoriaRepository()
        val reloj = FakeReloj()
        val sesiones = InMemorySesionRepository()

        // Mismos datos que siembra DatabaseSeeder para calidad_faon: username/PIN de la tabla de
        // credenciales de prueba, un único rol (CALIDAD) y el nombre del técnico de ejemplo.
        val hash = pinHasher.crearHash("8642")
        val tecnico = Usuario.crear(
            id = "cal-faon", username = "calidad_faon", nombres = "Miguel Vargas", dni = "40000001",
            pinHash = hash.hash, pinSalt = hash.salt, activo = true, roles = listOf(Rol.CALIDAD),
            updatedAt = 0L,
        ).getOrThrow()
        usuarios.insertar(tecnico)

        val loginViewModel = LoginViewModel(
            loginOfflineUseCase = LoginOfflineUseCase(usuarios, pinHasher, auditoria, reloj, FakeDeviceIdProvider()),
            seleccionarRolUseCase = SeleccionarRolUseCase(sesiones),
            vincularServidor = pe.ecolecta.domain.usecase.sync.VincularServidorUseCase(
                pe.ecolecta.data.remote.ServidorWebNoConfigurado(), {}, this,
            ),
        )

        // --- Paso 1: la pantalla de login, tal cual la usa el usuario ---
        loginViewModel.onEvent(LoginUiEvent.UsernameCambia("calidad_faon"))
        loginViewModel.onEvent(LoginUiEvent.PinCambia("8642"))
        loginViewModel.onEvent(LoginUiEvent.Ingresar)
        advanceUntilIdle()

        val estadoLogin = loginViewModel.uiState.value
        assertNull(estadoLogin.error, "El login no debería fallar con calidad_faon/8642")
        assertTrue(estadoLogin.sesionIniciada, "Con un solo rol (CALIDAD) debe entrar directo, sin pedir selección de rol")
        assertFalse(estadoLogin.requiereSeleccionRol)

        val sesion = ObtenerSesionUseCase(sesiones)().first()
        assertNotNull(sesion, "Debe quedar una sesión activa tras iniciar sesión")
        assertEquals(Rol.CALIDAD, sesion.rolActivo)
        assertEquals("Miguel Vargas", sesion.usuario.nombres)

        // --- Paso 2: con esa sesión activa, el panel de Técnico de calidad debe cargar sin errores ---
        val proveedores = FakeProveedorRepository()
        val zonaFaon = Zona("z-faon", "FAON-MARKAPAJO", true)
        val zonas = object : ZonaRepository {
            override fun observarTodas() = flowOf(listOf(zonaFaon))
            override fun observarActivas() = observarTodas()
            override suspend fun obtenerPorId(id: String) = zonaFaon
            override suspend fun insertar(zona: Zona) {}
            override suspend fun actualizar(zona: Zona) {}
            override suspend fun desactivar(id: String) {}
            override suspend fun contarProveedoresEnZona(id: String) = 0L
        }
        val controles = object : ControlCalidadRepository {
            override fun observarTodos() = flowOf(emptyList<ControlCalidad>())
            override fun observarPorUsuario(usuarioId: String) = flowOf(emptyList<ControlCalidad>())
            override suspend fun obtenerPorId(id: String) = null
            override suspend fun existeCodigoMuestra(codigo: String) = false
            override suspend fun insertar(control: ControlCalidad) {}
            override suspend fun obtenerZonaAsignada(usuarioId: String) = zonaFaon.id
        }

        val calidadViewModel = CalidadViewModel(
            repository = controles,
            listarProveedoresUseCase = ListarProveedoresUseCase(proveedores),
            obtenerSesionUseCase = ObtenerSesionUseCase(sesiones),
            reloj = reloj,
            listarZonasUseCase = ListarZonasUseCase(zonas),
        )
        advanceUntilIdle()

        val estadoCalidad = calidadViewModel.uiState.value
        assertFalse(estadoCalidad.cargando, "El panel de calidad debe terminar de cargar")
        assertNull(estadoCalidad.error)
        assertEquals("Miguel Vargas", estadoCalidad.tecnico)
        assertEquals(PasoCalidad.INICIO, estadoCalidad.paso)
        assertEquals(listOf(zonaFaon), estadoCalidad.zonas)
    }
}
