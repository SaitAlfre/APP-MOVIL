package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.proveedor.ObtenerDetalleEntregaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

class ObtenerDetalleEntregaUseCaseTest {
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = ObtenerDetalleEntregaUseCase(entregaRepository)

    private fun entrega(id: String, proveedorId: String): Entrega = Entrega.crear(
        id = id, jornadaId = "j1", proveedorId = proveedorId, usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
        litros = 20.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
    ).getOrThrow()

    @Test
    fun `devuelve la entrega cuando pertenece al proveedor`() = runTest {
        entregaRepository.sembrar(entrega("e1", "p1"))

        assertEquals("e1", useCase("e1", "p1")?.id)
    }

    @Test
    fun `no permite ver una entrega de otro proveedor`() = runTest {
        entregaRepository.sembrar(entrega("e1", "p1"))

        assertNull(useCase("e1", "p2"))
    }

    @Test
    fun `devuelve null si la entrega no existe`() = runTest {
        assertNull(useCase("no-existe", "p1"))
    }

    @Test
    fun `una entrega anulada sigue siendo visible en el detalle`() = runTest {
        val anulada = entrega("e1", "p1").copy(anulada = true)
        entregaRepository.sembrar(anulada)

        val resultado = useCase("e1", "p1")

        assertEquals(true, resultado?.anulada)
    }

    @Test
    fun `una entrega en conflicto es visible pero conserva su estado`() = runTest {
        val enConflicto = entrega("e1", "p1").copy(syncState = SyncState.CONFLICT, litrosServidor = 18.0)
        entregaRepository.sembrar(enConflicto)

        val resultado = useCase("e1", "p1")

        assertEquals(SyncState.CONFLICT, resultado?.syncState)
    }
}
