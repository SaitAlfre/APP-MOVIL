package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaRepository

class ObtenerJornadaUseCase(private val jornadaRepository: JornadaRepository) {
    suspend operator fun invoke(id: String): Jornada? = jornadaRepository.obtenerPorId(id)
}
