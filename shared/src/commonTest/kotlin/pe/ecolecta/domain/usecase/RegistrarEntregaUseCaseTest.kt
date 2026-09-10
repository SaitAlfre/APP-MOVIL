package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertTrue

class RegistrarEntregaUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val entregaRepository = FakeEntregaRepository()
    private val useCase = RegistrarEntregaUseCase(entregaRepository, proveedorRepository, FakeReloj(), FakeDeviceIdProvider())

    private suspend fun sembrarProveedor(
        tachos: Int = 2,
        capacidadTachoL: Double = 40.0,
        estado: EstadoProveedor = EstadoProveedor.ACTIVO,
    ): Proveedor {
        val proveedor = Proveedor.crear(
            id = "p1", codigo = "PRV-001", nombres = "Mario Quispe", dni = "10000001",
            telefono = null, direccion = null, zonaId = "z1", tachos = tachos, capacidadTachoL = capacidadTachoL,
            estado = estado, updatedAt = 0L,
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)
        return proveedor
    }

    @Test
    fun `bloquea si el proveedor esta suspendido`() = runTest {
        sembrarProveedor(estado = EstadoProveedor.SUSPENDIDO)

        val resultado = useCase("j1", "p1", "u1", "z1", "v1", litros = 20.0, tachos = 1, observaciones = null)

        assertTrue(resultado.isFailure)
        assertIs<EntregaInvalidaException.ProveedorNoActivo>(resultado.exceptionOrNull())
    }

    @Test
    fun `bloquea si el proveedor esta retirado`() = runTest {
        sembrarProveedor(estado = EstadoProveedor.RETIRADO)

        val resultado = useCase("j1", "p1", "u1", "z1", "v1", litros = 20.0, tachos = 1, observaciones = null)

        assertTrue(resultado.isFailure)
        assertIs<EntregaInvalidaException.ProveedorNoActivo>(resultado.exceptionOrNull())
    }

    @Test
    fun `bloquea si los litros superan la capacidad fisica del tacho`() = runTest {
        sembrarProveedor(tachos = 2, capacidadTachoL = 40.0) // capacidad total = 80 L

        val resultado = useCase(
            jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 90.0, tachos = 2, observaciones = null,
        )

        assertTrue(resultado.isFailure)
        assertIs<EntregaInvalidaException.SuperaCapacidad>(resultado.exceptionOrNull())
    }

    @Test
    fun `guarda sin advertencia cuando no hay historial previo`() = runTest {
        sembrarProveedor()

        val resultado = useCase("j1", "p1", "u1", "z1", "v1", litros = 20.0, tachos = 1, observaciones = null)

        assertTrue(resultado.isSuccess)
        assertFalse(resultado.getOrThrow().advertenciaDesviacion)
    }

    @Test
    fun `marca advertencia cuando la desviacion supera 40 porciento del promedio`() = runTest {
        sembrarProveedor()
        repeat(3) { entregaRepository.sembrar(entregaDe(litros = 20.0, offset = it)) }

        val resultado = useCase("j1", "p1", "u1", "z1", "v1", litros = 35.0, tachos = 1, observaciones = null)

        assertTrue(resultado.isSuccess)
        assertTrue(resultado.getOrThrow().advertenciaDesviacion)
    }

    private fun entregaDe(litros: Double, offset: Int): Entrega = Entrega.crear(
        id = "e$offset", jornadaId = "j0", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
        litros = litros, tachos = 1, observaciones = null, registradoEn = offset.toLong(), deviceId = "dev", loteId = null,
    ).getOrThrow()
}
