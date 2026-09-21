package pe.ecolecta.domain.usecase.jornada

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaRepository

/** Igual que [ListarJornadasUseCase], pero reactivo: usado por ADMIN para ver en vivo las jornadas que ACOPIADOR abre o cierra. */
class ObservarJornadasUseCase(private val jornadaRepository: JornadaRepository) {
    operator fun invoke(): Flow<List<Jornada>> = jornadaRepository.observarTodas()
}
