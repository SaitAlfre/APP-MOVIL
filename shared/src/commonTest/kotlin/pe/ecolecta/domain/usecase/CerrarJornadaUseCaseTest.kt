package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.domain.JornadaDelDiaCerradaException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Instant

class CerrarJornadaUseCaseTest {
    private val jornadaRepository = FakeJornadaRepository()
    private val jornadaEnCursoRepository = InMemoryJornadaEnCursoRepository()
    private val reloj = FakeReloj()
    private val auditoriaRepository = FakeAuditoriaRepository()
    private val abrirJornadaUseCase = AbrirJornadaUseCase(jornadaRepository, jornadaEnCursoRepository, reloj)
    private val useCase = CerrarJornadaUseCase(
        jornadaRepository,
        jornadaEnCursoRepository,
        reloj,
        auditoriaRepository,
        FakeDeviceIdProvider(),
    )

    @Test
    fun `cerrar una jornada abierta la marca cerrada y limpia la jornada en curso`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        val resultado = useCase(jornada.id)

        assertTrue(resultado.isSuccess)
        assertNull(jornadaEnCursoRepository.observar().first())
        val jornadaGuardada = jornadaRepository.obtenerPorId(jornada.id)
        assertTrue(jornadaGuardada?.cerradaEn != null, "la jornada debe quedar marcada como cerrada")
    }

    @Test
    fun `si falla el cierre en el repositorio la jornada sigue abierta`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        jornadaRepository.fallarAlCerrar = true

        val resultado = useCase(jornada.id)

        assertTrue(resultado.isFailure)
        assertEquals(jornada, jornadaEnCursoRepository.observar().first(), "la jornada en curso no debe limpiarse si falla el cierre")
        val jornadaGuardada = jornadaRepository.obtenerPorId(jornada.id)
        assertNull(jornadaGuardada?.cerradaEn, "la jornada debe seguir abierta")
    }

    @Test
    fun `cerrar dos veces es idempotente y conserva la hora de cierre original`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        useCase(jornada.id).getOrThrow()
        val cerradaEn = jornadaRepository.obtenerPorId(jornada.id)?.cerradaEn
        val despues = CerrarJornadaUseCase(
            jornadaRepository, jornadaEnCursoRepository, FakeReloj(instante = Instant.fromEpochMilliseconds(1_700_000_999_000L)),
            auditoriaRepository, FakeDeviceIdProvider(),
        )

        val segundo = despues(jornada.id)

        assertTrue(segundo.isSuccess)
        assertEquals(cerradaEn, jornadaRepository.obtenerPorId(jornada.id)?.cerradaEn)
    }

    @Test
    fun `ciclo abrir cerrar e intentar abrir el mismo dia explica el motivo y conserva el cierre`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        useCase(jornada.id).getOrThrow()

        val reapertura = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1")

        assertIs<JornadaDelDiaCerradaException>(reapertura.exceptionOrNull())
        assertNull(jornadaEnCursoRepository.observar().first())
        assertFalse(jornadaRepository.obtenerPorId(jornada.id)!!.estaAbierta)
    }

    @Test
    fun `cada cierre efectivo queda auditado una sola vez`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        useCase(jornada.id).getOrThrow()
        useCase(jornada.id).getOrThrow()

        val registros = auditoriaRepository.filtrar(accion = AccionAuditoria.CERRAR_JORNADA)
        assertEquals(1, registros.size, "el segundo cierre (idempotente) no genera otro registro")
        assertEquals(jornada.id, registros.single().entidadId)
    }
}
