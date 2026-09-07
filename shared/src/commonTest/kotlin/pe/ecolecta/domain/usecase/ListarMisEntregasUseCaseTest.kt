package pe.ecolecta.domain.usecase

import app.cash.turbine.test
import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.usecase.proveedor.ListarMisEntregasUseCase
import kotlin.test.Test
import kotlin.test.assertTrue

class ListarMisEntregasUseCaseTest {
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = ListarMisEntregasUseCase(entregaRepository)

    @Test
    fun `sin entregas emite una lista vacia`() = runTest {
        useCase("p1").test {
            assertTrue(awaitItem().isEmpty())
        }
    }

    @Test
    fun `solo emite entregas del proveedor solicitante`() = runTest {
        entregaRepository.sembrar(
            Entrega.crear(
                id = "e1", jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
                litros = 20.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
            ).getOrThrow(),
        )
        entregaRepository.sembrar(
            Entrega.crear(
                id = "e2", jornadaId = "j1", proveedorId = "p2", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
                litros = 20.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
            ).getOrThrow(),
        )

        useCase("p1").test {
            val resultado = awaitItem()
            assertTrue(resultado.size == 1 && resultado.first().id == "e1")
        }
    }
}
