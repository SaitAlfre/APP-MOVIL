package pe.ecolecta.domain.usecase.zona

import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.ZonaRepository

class ActualizarZonaUseCase(private val zonaRepository: ZonaRepository) {
    suspend operator fun invoke(id: String, nombre: String, activo: Boolean): Result<Unit> {
        val zona = Zona.crear(id, nombre, activo).getOrElse { return Result.failure(it) }
        return runCatching { zonaRepository.actualizar(zona) }
    }
}
