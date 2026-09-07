package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.ProveedorInvalidoException
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.usecase.proveedor.CrearProveedorUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class CrearProveedorUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val useCase = CrearProveedorUseCase(proveedorRepository, FakeReloj())

    @Test
    fun `crea proveedor con valores por defecto`() = runTest {
        val resultado = useCase(
            codigo = "PRV-100", nombres = "Mario Quispe", dni = "10000099",
            telefono = null, direccion = null, zonaId = "zona-1",
        )

        val proveedor = resultado.getOrThrow()
        assertEquals(1, proveedor.tachos)
        assertEquals(40.0, proveedor.capacidadTachoL)
        assertEquals(40.0, proveedor.capacidadTotalL)
    }

    @Test
    fun `rechaza codigo duplicado`() = runTest {
        useCase("PRV-100", "Mario Quispe", "10000099", null, null, "zona-1")

        val resultado = useCase("PRV-100", "Otro Proveedor", "10000098", null, null, "zona-1")

        assertIs<ProveedorInvalidoException.CodigoDuplicado>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechaza dni duplicado`() = runTest {
        useCase("PRV-100", "Mario Quispe", "10000099", null, null, "zona-1")

        val resultado = useCase("PRV-101", "Otro Proveedor", "10000099", null, null, "zona-1")

        assertIs<ProveedorInvalidoException.DniDuplicado>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechaza tachos no positivos`() = runTest {
        val resultado = useCase("PRV-100", "Mario Quispe", "10000099", null, null, "zona-1", tachos = 0)
        assertTrue(resultado.isFailure)
        assertIs<ProveedorInvalidoException.TachosInvalidos>(resultado.exceptionOrNull())
    }
}
