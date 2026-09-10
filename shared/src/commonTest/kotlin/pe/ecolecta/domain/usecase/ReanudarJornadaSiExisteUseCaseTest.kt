package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.jornada.ReanudarJornadaSiExisteUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

/**
 * Cubre el caso que motivó este arreglo: una jornada abierta de un día anterior no debe quedar
 * huérfana (invisible en la UI, pero todavía ocupando su zona) — debe poder recuperarse igual que
 * una de hoy, para que el usuario la cierre con el flujo normal.
 */
class ReanudarJornadaSiExisteUseCaseTest {
    private val jornadaRepository = FakeJornadaRepository()
    private val jornadaEnCursoRepository = InMemoryJornadaEnCursoRepository()
    private val useCase = ReanudarJornadaSiExisteUseCase(jornadaRepository, jornadaEnCursoRepository)

    private fun jornada(
        id: String = "j1",
        usuarioId: String = "u1",
        fecha: LocalDate = LocalDate(2026, 1, 15),
        abiertaEn: Long = 0L,
        cerradaEn: Long? = null,
    ) = Jornada(
        id = id, usuarioId = usuarioId, zonaId = "zona-1", vehiculoId = "veh-1",
        fecha = fecha, abiertaEn = abiertaEn, cerradaEn = cerradaEn, syncState = SyncState.PENDING,
    )

    @Test
    fun `sin ninguna jornada, no hay nada que retomar`() = runTest {
        assertNull(useCase("u1"))
    }

    @Test
    fun `jornada abierta hoy se retoma`() = runTest {
        val j = jornada(fecha = LocalDate(2026, 1, 15))
        jornadaRepository.insertar(j)
        assertEquals(j, useCase("u1"))
    }

    @Test
    fun `jornada abierta de un dia anterior tambien se retoma (cambio de dia)`() = runTest {
        val deAyer = jornada(id = "j-ayer", fecha = LocalDate(2026, 1, 14))
        jornadaRepository.insertar(deAyer)
        assertEquals(deAyer, useCase("u1"))
    }

    @Test
    fun `jornada cerrada no se retoma`() = runTest {
        jornadaRepository.insertar(jornada(cerradaEn = 999L))
        assertNull(useCase("u1"))
    }

    @Test
    fun `jornada abierta de otro usuario no se retoma`() = runTest {
        jornadaRepository.insertar(jornada(id = "j-otro", usuarioId = "u2"))
        assertNull(useCase("u1"))
    }

    @Test
    fun `si hay varias abiertas del mismo usuario, se retoma la mas antigua primero`() = runTest {
        val masVieja = jornada(id = "j-vieja", abiertaEn = 100L)
        val masNueva = jornada(id = "j-nueva", abiertaEn = 200L)
        jornadaRepository.insertar(masNueva)
        jornadaRepository.insertar(masVieja)
        assertEquals(masVieja, useCase("u1"))
    }
}
