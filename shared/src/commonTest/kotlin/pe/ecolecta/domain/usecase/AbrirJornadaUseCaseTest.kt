package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.domain.ZonaOcupadaException
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
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
}
