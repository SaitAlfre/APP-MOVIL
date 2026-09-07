package pe.ecolecta.domain.usecase.zona

import pe.ecolecta.domain.ZonaInvalidaException
import pe.ecolecta.domain.repository.ZonaRepository

class DesactivarZonaUseCase(private val zonaRepository: ZonaRepository) {
    suspend operator fun invoke(id: String): Result<Unit> {
        if (zonaRepository.contarProveedoresEnZona(id) > 0) {
            return Result.failure(ZonaInvalidaException.ConProveedoresActivos)
        }
        return runCatching { zonaRepository.desactivar(id) }
    }
}
