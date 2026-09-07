package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.proveedor.FiltrarMisEntregasUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

class FiltrarMisEntregasUseCaseTest {
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = FiltrarMisEntregasUseCase(entregaRepository)

    private fun entrega(id: String, proveedorId: String, registradoEn: Long, syncState: SyncState = SyncState.SYNCED): Entrega =
        Entrega.crear(
            id = id, jornadaId = "j1", proveedorId = proveedorId, usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 20.0, tachos = 1, observaciones = null, registradoEn = registradoEn, deviceId = "dev", loteId = null,
        ).getOrThrow().copy(syncState = syncState)

    @Test
    fun `solo devuelve entregas del proveedor solicitante`() = runTest {
        entregaRepository.sembrar(entrega("e1", "p1", 10L))
        entregaRepository.sembrar(entrega("e2", "p2", 10L))

        val resultado = useCase("p1")

        assertEquals(listOf("e1"), resultado.map { it.id })
    }

    @Test
    fun `filtra por rango de fechas`() = runTest {
        entregaRepository.sembrar(entrega("e1", "p1", 10L))
        entregaRepository.sembrar(entrega("e2", "p1", 500L))

        val resultado = useCase("p1", desde = 0L, hasta = 100L)

        assertEquals(listOf("e1"), resultado.map { it.id })
    }

    @Test
    fun `filtra por estado de sincronizacion`() = runTest {
        entregaRepository.sembrar(entrega("e1", "p1", 10L, SyncState.PENDING))
        entregaRepository.sembrar(entrega("e2", "p1", 20L, SyncState.SYNCED))

        val resultado = useCase("p1", syncState = SyncState.PENDING)

        assertTrue(resultado.all { it.syncState == SyncState.PENDING })
        assertEquals(1, resultado.size)
    }
}
