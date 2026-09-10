package pe.ecolecta.domain.usecase.seguimiento

import kotlinx.coroutines.CancellationException
import pe.ecolecta.domain.GuardadoUbicacionException
import pe.ecolecta.domain.repository.UbicacionAcopiadorLocalRepository

class GuardarUbicacionLocalUseCase(private val repository: UbicacionAcopiadorLocalRepository) {
    suspend operator fun invoke(
        usuarioId: String,
        jornadaId: String,
        zonaId: String,
        lat: Double,
        lng: Double,
        precisionM: Double,
        capturadaEn: Long,
        publicada: Boolean,
    ) {
        try {
            repository.guardar(usuarioId, jornadaId, zonaId, lat, lng, precisionM, capturadaEn, publicada)
        } catch (cancelacion: CancellationException) {
            throw cancelacion
        } catch (error: Exception) {
            throw GuardadoUbicacionException(error)
        }
    }
}
