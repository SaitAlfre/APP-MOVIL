package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.OrigenValorConflicto
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.conflicto.ResolverConflictoUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class ResolverConflictoUseCaseTest {
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val entregaRepository = FakeEntregaRepository(auditoriaRepository)
    private val useCase = ResolverConflictoUseCase(entregaRepository, FakeReloj(), FakeDeviceIdProvider())

    private suspend fun sembrarConflicto(): Entrega {
        val base = Entrega.crear(
            id = "e1", jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 24.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
        ).getOrThrow()
        val enConflicto = base.copy(syncState = SyncState.CONFLICT, litrosServidor = 21.0, tachosServidor = 1.0)
        entregaRepository.sembrar(enConflicto)
        return enConflicto
    }

    @Test
    fun `elegir el valor local deja la entrega pendiente para volver a sincronizar`() = runTest {
        sembrarConflicto()

        val resultado = useCase("e1", OrigenValorConflicto.LOCAL, motivo = "El acopiador confirma su valor", usuarioId = "admin1")

        assertTrue(resultado.isSuccess)
        val entrega = entregaRepository.obtenerPorId("e1")!!
        assertEquals(24.0, entrega.litros)
        assertEquals(SyncState.PENDING, entrega.syncState)
        assertEquals(null, entrega.litrosServidor)
    }

    @Test
    fun `elegir el valor del servidor marca la entrega como sincronizada`() = runTest {
        sembrarConflicto()

        val resultado = useCase("e1", OrigenValorConflicto.SERVIDOR, motivo = "El servidor tiene el valor correcto", usuarioId = "admin1")

        assertTrue(resultado.isSuccess)
        val entrega = entregaRepository.obtenerPorId("e1")!!
        assertEquals(21.0, entrega.litros)
        assertEquals(SyncState.SYNCED, entrega.syncState)
    }

    @Test
    fun `rechaza resolver sin motivo`() = runTest {
        sembrarConflicto()

        val resultado = useCase("e1", OrigenValorConflicto.LOCAL, motivo = "", usuarioId = "admin1")

        assertIs<EntregaInvalidaException.MotivoObligatorio>(resultado.exceptionOrNull())
    }

    @Test
    fun `rechaza resolver una entrega que no esta en conflicto`() = runTest {
        val entregaNormal = Entrega.crear(
            id = "e2", jornadaId = "j1", proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 10.0, tachos = 1, observaciones = null, registradoEn = 0L, deviceId = "dev", loteId = null,
        ).getOrThrow()
        entregaRepository.sembrar(entregaNormal)

        val resultado = useCase("e2", OrigenValorConflicto.LOCAL, motivo = "motivo", usuarioId = "admin1")

        assertIs<EntregaInvalidaException.NoEsConflicto>(resultado.exceptionOrNull())
    }
}
