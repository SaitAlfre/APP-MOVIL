package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.ProveedorSesionException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import pe.ecolecta.domain.usecase.auth.SeleccionarRolUseCase
import pe.ecolecta.domain.usecase.proveedor.LoginProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorAsociadoUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class LoginProveedorUseCaseTest {
    private val pinHasher = Pbkdf2PinHasher()
    private val usuarioRepository = FakeUsuarioRepository()
    private val proveedorRepository = FakeProveedorRepository()
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val sesionRepository = InMemorySesionRepository()
    private val useCase = LoginProveedorUseCase(
        LoginOfflineUseCase(usuarioRepository, pinHasher, auditoriaRepository, FakeReloj(), FakeDeviceIdProvider()),
        ObtenerProveedorAsociadoUseCase(proveedorRepository),
        SeleccionarRolUseCase(sesionRepository),
    )

    private suspend fun sembrarUsuario(roles: List<Rol> = listOf(Rol.PROVEEDOR)): Usuario {
        val hash = pinHasher.crearHash("1234")
        val usuario = Usuario.crear(
            id = "u1", username = "mquispe", nombres = "Mario Quispe", dni = "10000001",
            pinHash = hash.hash, pinSalt = hash.salt, activo = true, roles = roles, updatedAt = 0L,
        ).getOrThrow()
        usuarioRepository.insertar(usuario)
        return usuario
    }

    private suspend fun sembrarProveedor(usuarioId: String?, estado: EstadoProveedor = EstadoProveedor.ACTIVO): Proveedor {
        val proveedor = Proveedor.crear(
            id = "p1", codigo = "PRV-001", nombres = "Mario Quispe", dni = "10000001",
            telefono = null, direccion = null, zonaId = "z1", estado = estado, updatedAt = 0L, usuarioId = usuarioId,
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)
        return proveedor
    }

    @Test
    fun `login exitoso establece la sesion con rol PROVEEDOR y devuelve el proveedor vinculado`() = runTest {
        sembrarUsuario()
        sembrarProveedor(usuarioId = "u1")

        val resultado = useCase("mquispe", "1234")

        assertTrue(resultado.isSuccess)
        assertEquals("p1", resultado.getOrThrow().id)
        assertEquals(Rol.PROVEEDOR, sesionRepository.observar().first()?.rolActivo)
    }

    @Test
    fun `pin incorrecto falla igual que en el login generico`() = runTest {
        sembrarUsuario()
        sembrarProveedor(usuarioId = "u1")

        val resultado = useCase("mquispe", "0000")

        assertIs<PinInvalidoException.Incorrecto>(resultado.exceptionOrNull())
    }

    @Test
    fun `usuario sin rol PROVEEDOR es rechazado`() = runTest {
        sembrarUsuario(roles = listOf(Rol.ACOPIADOR))
        sembrarProveedor(usuarioId = "u1")

        val resultado = useCase("mquispe", "1234")

        assertIs<ProveedorSesionException.SinRolProveedor>(resultado.exceptionOrNull())
    }

    @Test
    fun `usuario con rol PROVEEDOR pero sin proveedor vinculado es rechazado`() = runTest {
        sembrarUsuario()

        val resultado = useCase("mquispe", "1234")

        assertIs<ProveedorSesionException.SinProveedorAsociado>(resultado.exceptionOrNull())
    }

    @Test
    fun `proveedor suspendido puede iniciar sesion igualmente`() = runTest {
        sembrarUsuario()
        sembrarProveedor(usuarioId = "u1", estado = EstadoProveedor.SUSPENDIDO)

        val resultado = useCase("mquispe", "1234")

        assertTrue(resultado.isSuccess)
        assertEquals(EstadoProveedor.SUSPENDIDO, resultado.getOrThrow().estado)
    }

    @Test
    fun `proveedor retirado puede iniciar sesion para consulta historica`() = runTest {
        sembrarUsuario()
        sembrarProveedor(usuarioId = "u1", estado = EstadoProveedor.RETIRADO)

        val resultado = useCase("mquispe", "1234")

        assertTrue(resultado.isSuccess)
        assertEquals(EstadoProveedor.RETIRADO, resultado.getOrThrow().estado)
    }
}
