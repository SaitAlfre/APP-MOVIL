package pe.ecolecta.domain.usecase.jornada

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaEnCursoRepository

class ObtenerJornadaEnCursoUseCase(private val jornadaEnCursoRepository: JornadaEnCursoRepository) {
    operator fun invoke(): Flow<Jornada?> = jornadaEnCursoRepository.observar()
}
