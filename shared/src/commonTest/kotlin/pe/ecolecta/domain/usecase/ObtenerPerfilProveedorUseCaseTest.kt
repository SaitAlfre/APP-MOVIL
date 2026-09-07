package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

class ObtenerPerfilProveedorUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val useCase = ObtenerPerfilProveedorUseCase(proveedorRepository)

    @Test
    fun `devuelve el proveedor vinculado al usuario`() = runTest {
        val proveedor = Proveedor.crear(
            id = "p1", codigo = "PRV-001", nombres = "Mario Quispe", dni = "10000001",
            telefono = null, direccion = null, zonaId = "z1", updatedAt = 0L, usuarioId = "u1",
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)

        assertEquals("p1", useCase("u1")?.id)
    }

    @Test
    fun `devuelve null si el usuario no tiene proveedor vinculado`() = runTest {
        assertNull(useCase("usuario-inexistente"))
    }
}
