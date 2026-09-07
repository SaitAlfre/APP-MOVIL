package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.usecase.proveedor.ObtenerHistorialProveedorUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

class ObtenerHistorialProveedorUseCaseTest {
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = ObtenerHistorialProveedorUseCase(entregaRepository)

    @Test
    fun `sin entregas devuelve una pagina vacia`() = runTest {
        assertTrue(useCase("p1", pagina = 0).isEmpty())
    }

    @Test
    fun `pagina progresivamente de mas reciente a mas antigua`() = runTest {
        repeat(25) { i ->
            entregaRepository.sembrar(
                Entrega.crear(
                    id = "e$i", jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
                    litros = 20.0, tachos = 1, observaciones = null, registradoEn = i.toLong(), deviceId = "dev", loteId = null,
                ).getOrThrow(),
            )
        }

        val primeraPagina = useCase("p1", pagina = 0)
        val segundaPagina = useCase("p1", pagina = 1)

        assertEquals(ObtenerHistorialProveedorUseCase.TAMANO_PAGINA, primeraPagina.size)
        assertEquals("e24", primeraPagina.first().id, "debe empezar por la mas reciente")
        assertEquals(5, segundaPagina.size)
    }
}
