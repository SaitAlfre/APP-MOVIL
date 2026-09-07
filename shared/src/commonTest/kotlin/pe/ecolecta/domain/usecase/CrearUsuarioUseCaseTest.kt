package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.UsuarioInvalidoException
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.usuario.CrearUsuarioUseCase
import kotlin.test.Test
import kotlin.test.assertIs
import kotlin.test.assertTrue

class CrearUsuarioUseCaseTest {
    private val usuarioRepository = FakeUsuarioRepository()
    private val useCase = CrearUsuarioUseCase(usuarioRepository, Pbkdf2PinHasher(), FakeReloj())

    @Test
    fun `crea usuario cuando los datos son validos`() = runTest {
        val resultado = useCase("jperez", "Juan Pérez", "12345678", "1234", "1234", listOf(Rol.ACOPIADOR))

        assertTrue(resultado.isSuccess)
        assertTrue(usuarioRepository.existeUsername("jperez", ""))
    }

    @Test
    fun `rechaza pin con formato invalido`() = runTest {
        val resultado = useCase("jperez", "Juan Pérez", "12345678", "12", "12", listOf(Rol.ACOPIADOR))
        assertIs<PinInvalidoException.FormatoInvalido>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechaza si la confirmacion del pin no coincide`() = runTest {
        val resultado = useCase("jperez", "Juan Pérez", "12345678", "1234", "4321", listOf(Rol.ACOPIADOR))
        assertIs<PinInvalidoException.NoCoincideConfirmacion>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechaza username duplicado`() = runTest {
        useCase("jperez", "Juan Pérez", "12345678", "1234", "1234", listOf(Rol.ACOPIADOR))

        val resultado = useCase("jperez", "Otro Nombre", "87654321", "1234", "1234", listOf(Rol.ADMIN))

        assertIs<UsuarioInvalidoException.UsernameDuplicado>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechaza sin ningun rol asignado`() = runTest {
        val resultado = useCase("jperez", "Juan Pérez", "12345678", "1234", "1234", emptyList())
        assertIs<UsuarioInvalidoException.SinRoles>(resultado.exceptionOrNull())
    }
}
