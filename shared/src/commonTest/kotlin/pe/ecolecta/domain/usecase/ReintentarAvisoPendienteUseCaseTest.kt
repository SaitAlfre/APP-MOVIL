package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeAvisoRemotoPendienteRepository
import pe.ecolecta.domain.fake.FakeRutaAcopioRepository
import pe.ecolecta.domain.model.AvisoRemotoPendiente
import pe.ecolecta.domain.usecase.seguimiento.ReintentarAvisoPendienteUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotNull
import kotlin.test.assertNull

class ReintentarAvisoPendienteUseCaseTest {
    private val avisoRemotoPendienteRepository = FakeAvisoRemotoPendienteRepository()
    private val rutaAcopioRepository = FakeRutaAcopioRepository()
    private val useCase = ReintentarAvisoPendienteUseCase(avisoRemotoPendienteRepository, rutaAcopioRepository)

    @Test
    fun `sin aviso pendiente, no publica nada`() = runTest {
        useCase("u1")

        assertNull(rutaAcopioRepository.ultimoEstadoPublicado)
    }

    @Test
    fun `reintento exitoso publica el aviso y lo borra`() = runTest {
        avisoRemotoPendienteRepository.guardar(
            AvisoRemotoPendiente("u1", "zona-1", "j1", jornadaAbiertaEn = 10L, secuenciaEn = 20L, jornadaAbierta = false, creadoEn = 20L),
        )

        useCase("u1")

        val publicado = rutaAcopioRepository.ultimoEstadoPublicado
        assertEquals(false, publicado?.jornadaAbierta)
        assertNull(avisoRemotoPendienteRepository.obtener("u1"), "el aviso debe borrarse tras publicarse con éxito")
    }

    @Test
    fun `reintento fallido conserva el aviso pendiente`() = runTest {
        avisoRemotoPendienteRepository.guardar(
            AvisoRemotoPendiente("u1", "zona-1", "j1", jornadaAbiertaEn = 10L, secuenciaEn = 20L, jornadaAbierta = null, creadoEn = 20L),
        )
        rutaAcopioRepository.fallarPublicaciones = true

        useCase("u1")

        assertNotNull(avisoRemotoPendienteRepository.obtener("u1"), "un reintento fallido no debe borrar el aviso")
    }
}
