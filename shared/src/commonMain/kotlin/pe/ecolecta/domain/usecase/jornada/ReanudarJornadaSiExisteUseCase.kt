package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

/** Al entrar al módulo Acopiador, retoma la jornada abierta de hoy si existe (evita repetir zona/vehículo tras reabrir la app). */
class ReanudarJornadaSiExisteUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(usuarioId: String): Jornada? {
        val jornada = jornadaRepository.obtenerPorUsuarioYFecha(usuarioId, reloj.hoy())?.takeIf { it.estaAbierta }
        if (jornada != null) jornadaEnCursoRepository.establecer(jornada)
        return jornada
    }
}
