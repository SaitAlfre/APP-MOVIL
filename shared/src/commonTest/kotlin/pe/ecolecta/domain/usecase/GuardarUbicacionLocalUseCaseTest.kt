package pe.ecolecta.domain.usecase

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.GuardadoUbicacionException
import pe.ecolecta.domain.excepcionAEstadoSeguimiento
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.repository.UbicacionAcopiadorLocalRepository
import pe.ecolecta.domain.repository.UbicacionLocalAcopiador
import pe.ecolecta.domain.usecase.seguimiento.GuardarUbicacionLocalUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFailsWith
import kotlin.test.assertSame

class GuardarUbicacionLocalUseCaseTest {
    private val posicion = UbicacionLocalAcopiador("usuario", "jornada", "zona", -12.0, -75.0, 8.0, 1000L, false)

    @Test
    fun `fallo de tabla ausente conserva la causa y no se muestra como falta de GPS`() = runTest {
        val falloSql = IllegalStateException("no such table: ubicacion_acopiador_local")
        val caso = GuardarUbicacionLocalUseCase(RepositorioDePrueba(falloSql))

        val error = assertFailsWith<GuardadoUbicacionException> { guardar(caso) }

        assertSame(falloSql, error.cause)
        assertEquals(EstadoSeguimiento.ERROR_ALMACENAMIENTO, excepcionAEstadoSeguimiento(error))
    }

    @Test
    fun `la captura se guarda conservando precision y hora originales`() = runTest {
        val repositorio = RepositorioDePrueba()
        guardar(GuardarUbicacionLocalUseCase(repositorio))
        assertEquals(posicion, repositorio.obtener(posicion.usuarioId))
    }

    @Test
    fun `detener la captura no convierte cancelacion en un fallo de almacenamiento`() = runTest {
        val cancelacion = CancellationException("seguimiento detenido")
        val caso = GuardarUbicacionLocalUseCase(RepositorioDePrueba(cancelacion))
        val error = assertFailsWith<CancellationException> { guardar(caso) }
        assertSame(cancelacion, error)
    }

    private suspend fun guardar(caso: GuardarUbicacionLocalUseCase) = with(posicion) {
        caso(usuarioId, jornadaId, zonaId, lat, lng, precisionM, capturadaEn, publicada)
    }

    private class RepositorioDePrueba(private val fallo: Exception? = null) : UbicacionAcopiadorLocalRepository {
        private var posicion: UbicacionLocalAcopiador? = null

        override suspend fun guardar(
            usuarioId: String, jornadaId: String, zonaId: String, lat: Double, lng: Double,
            precisionM: Double, capturadaEn: Long, publicada: Boolean,
        ) {
            fallo?.let { throw it }
            posicion = UbicacionLocalAcopiador(usuarioId, jornadaId, zonaId, lat, lng, precisionM, capturadaEn, publicada)
        }

        override suspend fun obtener(usuarioId: String) = posicion?.takeIf { it.usuarioId == usuarioId }
        override suspend fun marcarPublicada(usuarioId: String) { posicion = obtener(usuarioId)?.copy(publicada = true) }
        override suspend fun eliminar(usuarioId: String) { if (posicion?.usuarioId == usuarioId) posicion = null }
    }
}
