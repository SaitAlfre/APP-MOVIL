package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.usecase.proveedor.ObtenerResumenEntregasUseCase
import kotlin.test.Test
import kotlin.test.assertEquals

class ObtenerResumenEntregasUseCaseTest {
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = ObtenerResumenEntregasUseCase(entregaRepository)

    private fun entrega(id: String, litros: Double, registradoEn: Long, anulada: Boolean = false): Entrega =
        Entrega.crear(
            id = id, jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = litros, tachos = 1, observaciones = null, registradoEn = registradoEn, deviceId = "dev", loteId = null,
        ).getOrThrow().copy(anulada = anulada)

    @Test
    fun `sin entregas en el rango devuelve un resumen vacio`() = runTest {
        val resumen = useCase("p1", desde = 0L, hasta = 100L)

        assertEquals(0, resumen.numeroEntregas)
        assertEquals(0.0, resumen.litrosTotales)
    }

    @Test
    fun `calcula totales promedio mayor y menor solo con entregas vigentes en el rango`() = runTest {
        entregaRepository.sembrar(entrega("e1", 20.0, registradoEn = 10L))
        entregaRepository.sembrar(entrega("e2", 30.0, registradoEn = 20L))
        entregaRepository.sembrar(entrega("e3", 100.0, registradoEn = 20L, anulada = true)) // anulada: no cuenta
        entregaRepository.sembrar(entrega("e4", 15.0, registradoEn = 500L)) // fuera de rango: no cuenta

        val resumen = useCase("p1", desde = 0L, hasta = 100L)

        assertEquals(2, resumen.numeroEntregas)
        assertEquals(50.0, resumen.litrosTotales)
        assertEquals(25.0, resumen.promedioPorEntrega)
        assertEquals(30.0, resumen.mayorEntrega)
        assertEquals(20.0, resumen.menorEntrega)
    }
}
