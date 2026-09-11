package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.ProveedorInvalidoException
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.usecase.proveedor.VincularUsuarioProveedorUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertNull

class VincularUsuarioProveedorUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val usuarioRepository = FakeUsuarioRepository()
    private val useCase = VincularUsuarioProveedorUseCase(proveedorRepository, usuarioRepository)

    private suspend fun proveedor(id: String = "prov-1") = Proveedor.crear(
        id = id, codigo = "PRV-1", nombres = "Mario Quispe", dni = "10000099",
        telefono = null, direccion = null, zonaId = "zona-1", updatedAt = 0L,
    ).getOrThrow().also { proveedorRepository.insertar(it) }

    private suspend fun usuario(id: String = "usr-1", roles: List<Rol> = listOf(Rol.PROVEEDOR)) = Usuario.crear(
        id = id, username = "usuario-$id", nombres = "Usuario Prueba", dni = "20000099",
        pinHash = "h", pinSalt = "s", activo = true, roles = roles, updatedAt = 0L,
    ).getOrThrow().also { usuarioRepository.insertar(it) }

    @Test
    fun `vincula un usuario con rol PROVEEDOR al proveedor y queda resoluble por obtenerPorUsuarioId`() = runTest {
        val p = proveedor()
        val u = usuario()

        val resultado = useCase(p.id, u.id)

        assertEquals(Unit, resultado.getOrThrow())
        assertEquals(p.id, proveedorRepository.obtenerPorUsuarioId(u.id)?.id)
    }

    @Test
    fun `rechaza vincular un usuario sin rol PROVEEDOR`() = runTest {
        val p = proveedor()
        val u = usuario(roles = listOf(Rol.ACOPIADOR))

        val resultado = useCase(p.id, u.id)

        assertIs<ProveedorInvalidoException.UsuarioSinRolProveedor>(resultado.exceptionOrNull())
        assertNull(proveedorRepository.obtenerPorUsuarioId(u.id))
    }

    @Test
    fun `rechaza vincular un usuario ya vinculado a otro proveedor`() = runTest {
        val p1 = proveedor("prov-1")
        val p2 = proveedor("prov-2")
        val u = usuario()
        useCase(p1.id, u.id).getOrThrow()

        val resultado = useCase(p2.id, u.id)

        assertIs<ProveedorInvalidoException.UsuarioYaVinculado>(resultado.exceptionOrNull())
        assertEquals(p1.id, proveedorRepository.obtenerPorUsuarioId(u.id)?.id)
    }

    @Test
    fun `revincular el mismo proveedor al mismo usuario no falla`() = runTest {
        val p = proveedor()
        val u = usuario()
        useCase(p.id, u.id).getOrThrow()

        val resultado = useCase(p.id, u.id)

        assertEquals(Unit, resultado.getOrThrow())
    }
}
