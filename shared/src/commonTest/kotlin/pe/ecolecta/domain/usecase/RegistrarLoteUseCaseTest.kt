package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.entrega.ItemLote
import pe.ecolecta.domain.usecase.entrega.RegistrarLoteUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class RegistrarLoteUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = RegistrarLoteUseCase(entregaRepository, proveedorRepository, FakeReloj(), FakeDeviceIdProvider())

    private suspend fun sembrarProveedor(
        id: String,
        capacidadTachoL: Double = 40.0,
        estado: EstadoProveedor = EstadoProveedor.ACTIVO,
    ): Proveedor {
        val proveedor = Proveedor.crear(
            id = id, codigo = "PRV-$id", nombres = "Proveedor $id", dni = "1000000$id",
            telefono = null, direccion = null, zonaId = "z1", tachos = 1, capacidadTachoL = capacidadTachoL,
            estado = estado, updatedAt = 0L,
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)
        return proveedor
    }

    @Test
    fun `si un proveedor del lote no esta activo no se guarda ninguna entrega del lote`() = runTest {
        sembrarProveedor("a")
        sembrarProveedor("b", estado = EstadoProveedor.SUSPENDIDO)

        val resultado = useCase(
            "j1", "u1", "z1", "v1",
            listOf(ItemLote("a", 20.0, 1), ItemLote("b", 18.0, 1)),
        )

        assertIs<EntregaInvalidaException.ProveedorNoActivo>(resultado.exceptionOrNull())
        assertTrue(entregaRepository.filtrar(jornadaId = "j1").isEmpty(), "el lote debe ser todo o nada")
    }

    @Test
    fun `rechaza un lote vacio`() = runTest {
        val resultado = useCase("j1", "u1", "z1", "v1", emptyList())
        assertIs<EntregaInvalidaException.LoteVacio>(resultado.exceptionOrNull())
    }

    @Test
    fun `guarda todas las entregas del lote compartiendo el mismo lote_id`() = runTest {
        sembrarProveedor("a")
        sembrarProveedor("b")

        val resultado = useCase(
            "j1", "u1", "z1", "v1",
            listOf(ItemLote("a", 20.0, 1), ItemLote("b", 18.0, 1)),
        )

        val entregas = resultado.getOrThrow()
        assertEquals(2, entregas.size)
        assertEquals(entregas[0].loteId, entregas[1].loteId)
        assertEquals(2, entregaRepository.filtrar(jornadaId = "j1").size)
    }

    @Test
    fun `si un item excede la capacidad no se guarda ninguna entrega del lote`() = runTest {
        sembrarProveedor("a")
        sembrarProveedor("b", capacidadTachoL = 10.0)

        val resultado = useCase(
            "j1", "u1", "z1", "v1",
            listOf(ItemLote("a", 20.0, 1), ItemLote("b", 50.0, 1)),
        )

        assertIs<EntregaInvalidaException.SuperaCapacidad>(resultado.exceptionOrNull())
        assertTrue(entregaRepository.filtrar(jornadaId = "j1").isEmpty(), "el lote debe ser todo o nada")
    }
}
