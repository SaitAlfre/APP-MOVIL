package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

class CerrarJornadaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(jornadaId: String): Result<Unit> = runCatching {
        jornadaRepository.cerrar(jornadaId, reloj.ahora().toEpochMilliseconds())
        jornadaEnCursoRepository.limpiar()
    }
}
