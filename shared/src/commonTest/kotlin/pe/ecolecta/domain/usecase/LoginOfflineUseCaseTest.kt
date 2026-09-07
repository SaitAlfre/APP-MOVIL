package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFailsWith
import kotlin.test.assertIs
import kotlin.test.assertTrue

class LoginOfflineUseCaseTest {
    private val pinHasher = Pbkdf2PinHasher()
    private val reloj = FakeReloj()
    private val usuarioRepository = FakeUsuarioRepository()
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val useCase = LoginOfflineUseCase(usuarioRepository, pinHasher, auditoriaRepository, reloj, FakeDeviceIdProvider())

    private suspend fun sembrarUsuario(pin: String = "1234", activo: Boolean = true): Usuario {
        val hash = pinHasher.crearHash(pin)
        val usuario = Usuario.crear(
            id = "u1", username = "jperez", nombres = "Juan Pérez", dni = "12345678",
            pinHash = hash.hash, pinSalt = hash.salt, activo = activo, roles = listOf(Rol.ACOPIADOR),
            updatedAt = 0L,
        ).getOrThrow()
        usuarioRepository.insertar(usuario)
        return usuario
    }

    @Test
    fun `login exitoso con pin correcto registra auditoria y resetea intentos`() = runTest {
        sembrarUsuario(pin = "1234")

        val resultado = useCase("jperez", "1234")

        assertTrue(resultado.isSuccess)
        assertEquals(0, resultado.getOrThrow().intentosFallidos)
        assertEquals(1, auditoriaRepository.insertados.size)
    }

    @Test
    fun `pin incorrecto incrementa intentos fallidos`() = runTest {
        sembrarUsuario(pin = "1234")

        val resultado = useCase("jperez", "0000")

        assertTrue(resultado.isFailure)
        assertIs<PinInvalidoException.Incorrecto>(resultado.exceptionOrNull())
        assertEquals(1, usuarioRepository.obtenerPorId("u1")?.intentosFallidos)
    }

    @Test
    fun `bloquea la cuenta tras 5 intentos fallidos`() = runTest {
        sembrarUsuario(pin = "1234")

        repeat(5) { useCase("jperez", "0000") }
        val resultado = useCase("jperez", "1234")

        assertTrue(resultado.isFailure)
        assertIs<PinInvalidoException.Bloqueado>(resultado.exceptionOrNull())
    }

    @Test
    fun `usuario inactivo no puede iniciar sesion`() = runTest {
        sembrarUsuario(pin = "1234", activo = false)

        val resultado = useCase("jperez", "1234")

        assertIs<PinInvalidoException.Inactivo>(resultado.exceptionOrNull())
    }

    @Test
    fun `usuario inexistente falla como pin incorrecto sin filtrar informacion`() = runTest {
        val resultado = useCase("nadie", "1234")

        assertIs<PinInvalidoException.Incorrecto>(resultado.exceptionOrNull())
        assertEquals(0, auditoriaRepository.insertados.size)
    }

    @Test
    fun `assertFailsWith documenta el tipo esperado de excepcion`() = runTest {
        sembrarUsuario(pin = "1234")
        val excepcion = assertFailsWith<PinInvalidoException.Incorrecto> {
            useCase("jperez", "9999").getOrThrow()
        }
        assertEquals("Usuario o PIN incorrecto.", excepcion.message)
    }
}
