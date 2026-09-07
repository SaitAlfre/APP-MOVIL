package pe.ecolecta.domain.usecase.zona

import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.ZonaRepository

class ObtenerZonaUseCase(private val zonaRepository: ZonaRepository) {
    suspend operator fun invoke(id: String): Zona? = zonaRepository.obtenerPorId(id)
}
