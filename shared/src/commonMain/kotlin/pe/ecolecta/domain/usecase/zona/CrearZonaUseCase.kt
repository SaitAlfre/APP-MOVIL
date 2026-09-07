package pe.ecolecta.domain.usecase.zona

import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.ZonaRepository

class CrearZonaUseCase(private val zonaRepository: ZonaRepository) {
    suspend operator fun invoke(nombre: String, activo: Boolean = true): Result<Zona> {
        val zona = Zona.crear(nuevoId(), nombre, activo).getOrElse { return Result.failure(it) }
        return runCatching {
            zonaRepository.insertar(zona)
            zona
        }
    }
}
