package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class AnularEntregaUseCaseTest {
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val entregaRepository = FakeEntregaRepository(auditoriaRepository)
    private val useCase = AnularEntregaUseCase(entregaRepository, FakeReloj(), FakeDeviceIdProvider())

    private suspend fun sembrarEntrega(): Entrega {
        val entrega = Entrega.crear(
            id = "e1", jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 18.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
        ).getOrThrow()
        entregaRepository.sembrar(entrega)
        return entrega
    }

    @Test
    fun `anula la entrega sin eliminarla e inserta auditoria`() = runTest {
        sembrarEntrega()

        val resultado = useCase("e1", motivo = "Registrado por error", usuarioId = "admin1")

        assertTrue(resultado.isSuccess)
        val entrega = entregaRepository.obtenerPorId("e1")
        assertEquals(true, entrega?.anulada)
        assertEquals(AccionAuditoria.ANULAR, auditoriaRepository.insertados.first().accion)
    }

    @Test
    fun `rechaza anular sin motivo`() = runTest {
        sembrarEntrega()

        val resultado = useCase("e1", motivo = "", usuarioId = "admin1")

        assertIs<EntregaInvalidaException.MotivoObligatorio>(resultado.exceptionOrNull())
    }
}
