package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.TrasladoInvalidoException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeTrasladoRepository
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.usecase.traslado.AutorizarTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.RechazarTrasladoUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class AutorizarTrasladoUseCaseTest {
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val proveedorRepository = FakeProveedorRepository()
    private val trasladoRepository = FakeTrasladoRepository(proveedorRepository, auditoriaRepository)
    private val useCase = AutorizarTrasladoUseCase(trasladoRepository, FakeReloj(), FakeDeviceIdProvider())

    private suspend fun sembrar(): TrasladoZona {
        val proveedor = Proveedor.crear(
            id = "p1", codigo = "PRV-001", nombres = "Mario Quispe", dni = "10000001",
            telefono = null, direccion = null, zonaId = "zona-origen", updatedAt = 0L,
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)

        val traslado = TrasladoZona.crear(
            id = "t1", proveedorId = "p1", zonaOrigenId = "zona-origen", zonaDestinoId = "zona-destino",
            motivo = "Cambio de ruta", creadoEn = 0L,
        ).getOrThrow()
        trasladoRepository.insertar(traslado)
        return traslado
    }

    @Test
    fun `autorizar mueve al proveedor a la zona destino e inserta auditoria`() = runTest {
        sembrar()

        val resultado = useCase("t1", autorizadoPor = "admin1")

        assertTrue(resultado.isSuccess)
        assertEquals("zona-destino", proveedorRepository.obtenerPorId("p1")?.zonaId)
        assertEquals(EstadoTraslado.AUTORIZADO, trasladoRepository.obtenerPorId("t1")?.estado)
        assertEquals(1, auditoriaRepository.insertados.size)
    }

    @Test
    fun `rechaza autorizar un traslado ya resuelto`() = runTest {
        sembrar()
        useCase("t1", autorizadoPor = "admin1")

        val resultado = useCase("t1", autorizadoPor = "admin1")

        assertIs<TrasladoInvalidoException.NoPendiente>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechazar exige motivo y no cambia la zona del proveedor`() = runTest {
        sembrar()
        val rechazar = RechazarTrasladoUseCase(trasladoRepository, FakeReloj(), FakeDeviceIdProvider())

        assertIs<TrasladoInvalidoException.MotivoObligatorio>(rechazar("t1", "admin1", "  ").exceptionOrNull())
        assertEquals(EstadoTraslado.PENDIENTE, trasladoRepository.obtenerPorId("t1")?.estado)

        assertTrue(rechazar("t1", "admin1", "La ruta destino está llena").isSuccess)
        assertEquals(EstadoTraslado.RECHAZADO, trasladoRepository.obtenerPorId("t1")?.estado)
        assertEquals("zona-origen", proveedorRepository.obtenerPorId("p1")?.zonaId)
    }
}
