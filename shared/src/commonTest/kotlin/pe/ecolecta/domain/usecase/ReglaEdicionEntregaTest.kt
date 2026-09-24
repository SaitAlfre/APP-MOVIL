package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeGestionPortalRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.inicioSemanaProveedor
import pe.ecolecta.domain.usecase.admin.fechaLima
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

/**
 * Una entrega de jornada cerrada puede corregirse o anularse mientras su semana no esté liquidada;
 * con liquidación aprobada o pagada queda bloqueada para que el pago no quede desactualizado.
 */
class ReglaEdicionEntregaTest {
    private val auditoria = FakeAuditoriaRepository()
    private val entregas = FakeEntregaRepository(auditoria)
    private val portal = FakeGestionPortalRepository()
    private val regla = ReglaEdicionEntrega(portal)
    private val corregir = CorregirEntregaUseCase(entregas, FakeReloj(), FakeDeviceIdProvider(), regla)
    private val anular = AnularEntregaUseCase(entregas, FakeReloj(), FakeDeviceIdProvider(), regla)

    /** 10/09/2026 12:00 en Lima: semana contable del jueves 10 al miércoles 16. */
    private val registradoEn = 1_789_059_600_000L
    private val semana = inicioSemanaProveedor(fechaLima(registradoEn)).toString()

    private suspend fun sembrar(
        jornadaId: String = "jornada-cerrada",
        syncState: SyncState = SyncState.SYNCED,
        anulada: Boolean = false,
    ): Entrega {
        val entrega = Entrega.crear(
            id = "e1", jornadaId = jornadaId, proveedorId = "p1", usuarioId = "u1", zonaId = "z1", vehiculoId = "v1",
            litros = 18.0, tachos = 1, observaciones = null, registradoEn = registradoEn, deviceId = "dev", loteId = null,
        ).getOrThrow().copy(syncState = syncState, anulada = anulada)
        entregas.sembrar(entrega)
        return entrega
    }

    @Test
    fun `la semana usada en la prueba es la del jueves 10 de septiembre`() {
        assertEquals("2026-09-10", semana)
    }

    @Test
    fun `entrega de jornada cerrada sin liquidacion se corrige y vuelve a quedar pendiente de envio`() = runTest {
        sembrar()

        val resultado = corregir("e1", 20.0, 1, null, "Error de digitación", "admin")

        assertTrue(resultado.isSuccess)
        val entrega = entregas.obtenerPorId("e1")!!
        assertEquals(20.0, entrega.litros)
        assertEquals(SyncState.PENDING, entrega.syncState)
    }

    @Test
    fun `entrega de jornada abierta en semana en curso se corrige`() = runTest {
        sembrar(jornadaId = "jornada-abierta", syncState = SyncState.PENDING)

        assertTrue(corregir("e1", 19.0, 1, null, "Recuento", "acop").isSuccess)
    }

    @Test
    fun `liquidacion aprobada bloquea corregir y anular sin tocar la entrega`() = runTest {
        sembrar()
        portal.liquidar(semana, "p1", "APROBADA")

        val correccion = corregir("e1", 10.0, 1, null, "prueba", "admin").exceptionOrNull()
        val anulacion = anular("e1", "prueba", "admin").exceptionOrNull()

        assertIs<EntregaInvalidaException.SemanaLiquidada>(correccion)
        assertEquals(false, correccion.pagada)
        assertIs<EntregaInvalidaException.SemanaLiquidada>(anulacion)
        val entrega = entregas.obtenerPorId("e1")!!
        assertEquals(18.0, entrega.litros)
        assertEquals(false, entrega.anulada)
        assertEquals(0, auditoria.insertados.size)
    }

    @Test
    fun `liquidacion pagada tambien bloquea y lo indica`() = runTest {
        sembrar()
        portal.liquidar(semana, "p1", "PAGADA")

        val error = corregir("e1", 10.0, 1, null, "prueba", "admin").exceptionOrNull()

        assertIs<EntregaInvalidaException.SemanaLiquidada>(error)
        assertEquals(true, error.pagada)
    }

    @Test
    fun `una liquidacion anulada no bloquea`() = runTest {
        sembrar()
        portal.liquidar(semana, "p1", "ANULADA")

        assertTrue(anular("e1", "Registro duplicado", "admin").isSuccess)
    }

    @Test
    fun `la liquidacion de otra semana no bloquea`() = runTest {
        sembrar()
        portal.liquidar("2026-09-03", "p1", "PAGADA")

        assertTrue(corregir("e1", 17.5, 1, null, "Recuento", "admin").isSuccess)
    }

    @Test
    fun `anular es terminal y deja la entrega pendiente de envio`() = runTest {
        sembrar()

        assertTrue(anular("e1", "Registro duplicado", "admin").isSuccess)
        assertEquals(SyncState.PENDING, entregas.obtenerPorId("e1")!!.syncState)

        assertIs<EntregaInvalidaException.YaAnulada>(anular("e1", "otra vez", "admin").exceptionOrNull())
        assertIs<EntregaInvalidaException.YaAnulada>(corregir("e1", 5.0, 1, null, "tarde", "admin").exceptionOrNull())
        assertEquals(1, auditoria.insertados.size)
    }

    @Test
    fun `una entrega en conflicto se resuelve antes de corregirla o anularla`() = runTest {
        sembrar(syncState = SyncState.CONFLICT)

        assertIs<EntregaInvalidaException.ConflictoPendiente>(corregir("e1", 20.0, 1, null, "x", "admin").exceptionOrNull())
        assertIs<EntregaInvalidaException.ConflictoPendiente>(anular("e1", "x", "admin").exceptionOrNull())
    }

    @Test
    fun `corregir sin cambiar litros ni tachos no genera auditoria`() = runTest {
        sembrar()

        assertIs<EntregaInvalidaException.SinCambios>(corregir("e1", 18.0, 1, null, "prueba", "admin").exceptionOrNull())
        assertEquals(0, auditoria.insertados.size)
    }

    @Test
    fun `la anulacion guarda en auditoria el motivo y los valores anteriores`() = runTest {
        sembrar()

        anular("e1", "  Registro duplicado  ", "admin")

        val registro = auditoria.insertados.single()
        assertEquals("Registro duplicado", registro.motivo)
        assertEquals("litros=18.0;tachos=1", registro.valorAntes)
    }
}
