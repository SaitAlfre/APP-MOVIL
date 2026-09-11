package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemoryEstadoSeguimientoRepository
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.domain.fake.FakeAvisoRemotoPendienteRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeRutaAcopioRepository
import pe.ecolecta.domain.fake.FakeSeguimientoController
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.seguimiento.PublicarEstadoRemotoUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

class CerrarJornadaUseCaseTest {
    private val jornadaRepository = FakeJornadaRepository()
    private val jornadaEnCursoRepository = InMemoryJornadaEnCursoRepository()
    private val reloj = FakeReloj()
    private val seguimientoController = FakeSeguimientoController()
    private val estadoSeguimientoRepository = InMemoryEstadoSeguimientoRepository()
    private val rutaAcopioRepository = FakeRutaAcopioRepository()
    private val avisoRemotoPendienteRepository = FakeAvisoRemotoPendienteRepository()
    private val publicarEstadoRemotoUseCase = PublicarEstadoRemotoUseCase(rutaAcopioRepository, avisoRemotoPendienteRepository, reloj)
    private val abrirJornadaUseCase = AbrirJornadaUseCase(jornadaRepository, jornadaEnCursoRepository, reloj)
    private val useCase = CerrarJornadaUseCase(
        jornadaRepository,
        jornadaEnCursoRepository,
        reloj,
        seguimientoController,
        estadoSeguimientoRepository,
        publicarEstadoRemotoUseCase,
    )

    @Test
    fun `cerrar una jornada abierta la marca cerrada limpia la jornada en curso y detiene el seguimiento`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        estadoSeguimientoRepository.actualizar(EstadoSeguimiento.ACTIVO)

        val resultado = useCase(jornada.id)

        assertTrue(resultado.isSuccess)
        assertNull(jornadaEnCursoRepository.observar().first())
        assertTrue(seguimientoController.detenerLlamado)
        assertEquals(EstadoSeguimiento.INACTIVO, estadoSeguimientoRepository.observar().first())
        val jornadaGuardada = jornadaRepository.obtenerPorId(jornada.id)
        assertTrue(jornadaGuardada?.cerradaEn != null, "la jornada debe quedar marcada como cerrada")
    }

    @Test
    fun `cerrar jornada publica jornadaAbierta=false y seguimientoActivo=false en Firestore`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()

        useCase(jornada.id)

        val publicado = rutaAcopioRepository.ultimoEstadoPublicado
        assertEquals("zona-1", publicado?.zonaId)
        assertEquals(jornada.id, publicado?.jornadaId)
        assertEquals(jornada.abiertaEn, publicado?.jornadaAbiertaEn)
        assertFalse(publicado?.seguimientoActivo ?: true)
        assertEquals(false, publicado?.jornadaAbierta, "cerrar jornada es el único caso que debe publicar jornadaAbierta=false")
    }

    @Test
    fun `si la publicacion remota falla el cierre local no se revierte y queda un aviso pendiente`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        rutaAcopioRepository.fallarPublicaciones = true

        val resultado = useCase(jornada.id)

        assertTrue(resultado.isSuccess, "un fallo de red al publicar no debe deshacer el cierre local")
        val jornadaGuardada = jornadaRepository.obtenerPorId(jornada.id)
        assertTrue(jornadaGuardada?.cerradaEn != null)
        val aviso = avisoRemotoPendienteRepository.obtener("u1")
        assertEquals(false, aviso?.jornadaAbierta)
    }

    @Test
    fun `si falla el cierre en el repositorio la jornada sigue abierta y no se detiene el seguimiento`() = runTest {
        val jornada = abrirJornadaUseCase("u1", "zona-1", "vehiculo-1").getOrThrow()
        estadoSeguimientoRepository.actualizar(EstadoSeguimiento.ACTIVO)
        jornadaRepository.fallarAlCerrar = true

        val resultado = useCase(jornada.id)

        assertTrue(resultado.isFailure)
        assertEquals(jornada, jornadaEnCursoRepository.observar().first(), "la jornada en curso no debe limpiarse si falla el cierre")
        assertFalse(seguimientoController.detenerLlamado, "no debe intentar detener el seguimiento si la jornada no se cerró")
        assertEquals(EstadoSeguimiento.ACTIVO, estadoSeguimientoRepository.observar().first(), "el estado de seguimiento no debe cambiar si el cierre falló")
        assertNull(rutaAcopioRepository.ultimoEstadoPublicado, "no debe publicar nada si el cierre local falló")
        val jornadaGuardada = jornadaRepository.obtenerPorId(jornada.id)
        assertNull(jornadaGuardada?.cerradaEn, "la jornada debe seguir abierta")
    }
}
