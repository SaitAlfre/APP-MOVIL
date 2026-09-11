package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemoryEstadoSeguimientoRepository
import pe.ecolecta.domain.fake.FakeAvisoRemotoPendienteRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeRutaAcopioRepository
import pe.ecolecta.domain.fake.FakeSeguimientoController
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.usecase.seguimiento.DetenerSeguimientoUseCase
import pe.ecolecta.domain.usecase.seguimiento.PublicarEstadoRemotoUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Verifica el requisito explícito del Grupo 5: "detener seguimiento" nunca debe tocar `jornadaAbierta`
 * — solo un cierre de jornada exitoso puede finalizarla (ver [CerrarJornadaUseCaseTest]).
 */
class DetenerSeguimientoUseCaseTest {
    private val seguimientoController = FakeSeguimientoController()
    private val estadoSeguimientoRepository = InMemoryEstadoSeguimientoRepository()
    private val rutaAcopioRepository = FakeRutaAcopioRepository()
    private val avisoRemotoPendienteRepository = FakeAvisoRemotoPendienteRepository()
    private val reloj = FakeReloj()
    private val publicarEstadoRemotoUseCase = PublicarEstadoRemotoUseCase(rutaAcopioRepository, avisoRemotoPendienteRepository, reloj)
    private val useCase = DetenerSeguimientoUseCase(seguimientoController, estadoSeguimientoRepository, publicarEstadoRemotoUseCase)

    @Test
    fun `detener seguimiento publica seguimientoActivo=false sin tocar jornadaAbierta`() = runTest {
        useCase(usuarioId = "u1", zonaId = "zona-1", jornadaId = "j1", jornadaAbiertaEn = 123L)

        assertTrue(seguimientoController.detenerLlamado)
        assertEquals(EstadoSeguimiento.INACTIVO, estadoSeguimientoRepository.observar().first())
        val publicado = rutaAcopioRepository.ultimoEstadoPublicado
        assertEquals("zona-1", publicado?.zonaId)
        assertEquals("j1", publicado?.jornadaId)
        assertEquals(123L, publicado?.jornadaAbiertaEn)
        assertEquals(false, publicado?.seguimientoActivo)
        assertNull(publicado?.jornadaAbierta, "detener seguimiento nunca debe incluir un valor para jornadaAbierta")
    }

    @Test
    fun `si la publicacion remota falla queda un aviso pendiente sin jornadaAbierta`() = runTest {
        rutaAcopioRepository.fallarPublicaciones = true

        useCase(usuarioId = "u1", zonaId = "zona-1", jornadaId = "j1", jornadaAbiertaEn = 123L)

        val aviso = avisoRemotoPendienteRepository.obtener("u1")
        assertEquals("zona-1", aviso?.zonaId)
        assertNull(aviso?.jornadaAbierta)
    }
}
