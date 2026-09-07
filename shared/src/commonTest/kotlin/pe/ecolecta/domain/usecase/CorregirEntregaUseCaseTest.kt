package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class CorregirEntregaUseCaseTest {
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val entregaRepository = FakeEntregaRepository(auditoriaRepository)
    private val useCase = CorregirEntregaUseCase(entregaRepository, FakeReloj(), FakeDeviceIdProvider())

    private suspend fun sembrarEntrega(): Entrega {
        val entrega = Entrega.crear(
            id = "e1", jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 18.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
        ).getOrThrow()
        entregaRepository.sembrar(entrega)
        return entrega
    }

    @Test
    fun `corrige litros e inserta auditoria en la misma operacion`() = runTest {
        sembrarEntrega()

        val resultado = useCase("e1", litros = 20.0, tachos = 1, observaciones = null, motivo = "Error de digitación", usuarioId = "admin1")

        assertTrue(resultado.isSuccess)
        assertEquals(20.0, entregaRepository.obtenerPorId("e1")?.litros)
        assertEquals(1, auditoriaRepository.insertados.size)
        assertEquals(AccionAuditoria.CORREGIR, auditoriaRepository.insertados.first().accion)
        assertEquals("Error de digitación", auditoriaRepository.insertados.first().motivo)
    }

    @Test
    fun `rechaza corregir sin motivo`() = runTest {
        sembrarEntrega()

        val resultado = useCase("e1", litros = 20.0, tachos = 1, observaciones = null, motivo = "  ", usuarioId = "admin1")

        assertIs<EntregaInvalidaException.MotivoObligatorio>(resultado.exceptionOrNull())
        assertEquals(0, auditoriaRepository.insertados.size)
    }

    @Test
    fun `rechaza litros no positivos`() = runTest {
        sembrarEntrega()

        val resultado = useCase("e1", litros = 0.0, tachos = 1, observaciones = null, motivo = "motivo", usuarioId = "admin1")

        assertIs<EntregaInvalidaException.LitrosNoPositivos>(resultado.exceptionOrNull())
    }
}
