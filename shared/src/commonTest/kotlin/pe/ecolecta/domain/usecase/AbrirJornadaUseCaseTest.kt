package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.JornadaDelDiaCerradaException
import pe.ecolecta.domain.ZonaOcupadaException
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertNotEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

class AbrirJornadaUseCaseTest {
    private val jornadaRepository = FakeJornadaRepository()
    private val jornadaEnCursoRepository = InMemoryJornadaEnCursoRepository()
    private val reloj = FakeReloj()
    private val useCase = AbrirJornadaUseCase(jornadaRepository, jornadaEnCursoRepository, reloj)

    @Test
    fun `crea una jornada nueva y la marca como jornada en curso`() = runTest {
        val resultado = useCase("u1", "zona-1", "vehiculo-1")

        assertTrue(resultado.isSuccess)
        val jornada = resultado.getOrThrow()
        assertEquals("zona-1", jornada.zonaId)
        assertEquals(jornada, jornadaEnCursoRepository.observar().first())
    }

    @Test
    fun `retoma la jornada existente del dia en vez de crear otra`() = runTest {
        val primera = useCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        val segunda = useCase("u1", "zona-2", "vehiculo-2").getOrThrow()

        assertEquals(primera.id, segunda.id)
        assertEquals("zona-1", segunda.zonaId, "debe conservar la zona original, no la del segundo intento")
        assertEquals(1, jornadaRepository.observarTodas().first().size)
    }

    @Test
    fun `rechaza abrir jornada en una zona que ya tiene otro acopiador con jornada abierta`() = runTest {
        useCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        val resultado = useCase("u2", "zona-1", "vehiculo-2")

        assertTrue(resultado.isFailure)
        assertIs<ZonaOcupadaException>(resultado.exceptionOrNull())
        assertEquals(1, jornadaRepository.observarTodas().first().size, "no debe crear una segunda jornada en la zona ocupada")
    }

    @Test
    fun `permite que el mismo acopiador retome su jornada aunque siga abierta en su zona`() = runTest {
        val primera = useCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        val segunda = useCase("u1", "zona-1", "vehiculo-1")

        assertTrue(segunda.isSuccess)
        assertEquals(primera.id, segunda.getOrThrow().id)
    }

    @Test
    fun `permite abrir jornada en una zona distinta aunque otra zona este ocupada`() = runTest {
        useCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        val resultado = useCase("u2", "zona-2", "vehiculo-2")

        assertTrue(resultado.isSuccess)
        assertEquals("zona-2", resultado.getOrThrow().zonaId)
    }

    @Test
    fun `una jornada cerrada del dia no se retoma como abierta ni se reabre`() = runTest {
        val jornada = useCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        jornadaRepository.cerrar(jornada.id, cerradaEn = 1_700_000_500_000L)
        jornadaEnCursoRepository.limpiar()

        val resultado = useCase("u1", "zona-1", "vehiculo-1")

        assertIs<JornadaDelDiaCerradaException>(resultado.exceptionOrNull())
        assertNull(jornadaEnCursoRepository.observar().first(), "una jornada cerrada no debe quedar como jornada en curso")
        val guardadas = jornadaRepository.observarTodas().first()
        assertEquals(1, guardadas.size, "la regla es una jornada por usuario y dia: no se crea otra")
        assertEquals(1_700_000_500_000L, guardadas.single().cerradaEn, "no debe reabrir ni alterar la jornada cerrada")
    }

    @Test
    fun `al dia siguiente abre una jornada nueva y conserva la del dia anterior`() = runTest {
        val ayer = useCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        jornadaRepository.cerrar(ayer.id, cerradaEn = 1_700_000_500_000L)
        val manana = AbrirJornadaUseCase(jornadaRepository, jornadaEnCursoRepository, FakeReloj(fecha = LocalDate(2026, 1, 16)))

        val hoy = manana("u1", "zona-1", "vehiculo-1").getOrThrow()

        assertNotEquals(ayer.id, hoy.id)
        assertTrue(hoy.estaAbierta)
        assertEquals(hoy, jornadaEnCursoRepository.observar().first())
        assertEquals(2, jornadaRepository.observarTodas().first().size, "el historial del dia anterior se conserva")
        assertEquals(1_700_000_500_000L, jornadaRepository.obtenerPorId(ayer.id)?.cerradaEn)
    }
}
