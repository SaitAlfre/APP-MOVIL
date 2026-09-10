package pe.ecolecta.domain.usecase.auth

import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.usecase.seguimiento.DetenerSeguimientoUseCase

/**
 * El seguimiento de ubicación no debe seguir corriendo sin una sesión activa: cerrar sesión lo detiene
 * (publica `seguimientoActivo=false`) sin marcar la jornada como finalizada — solo un cierre de jornada
 * exitoso hace eso (ver [pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase]).
 */
class CerrarSesionUseCase(
    private val sesionRepository: SesionRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val detenerSeguimientoUseCase: DetenerSeguimientoUseCase,
) {
    suspend operator fun invoke() {
        val jornada = jornadaEnCursoRepository.observar().first()
        if (jornada != null && jornada.estaAbierta) {
            detenerSeguimientoUseCase(
                usuarioId = jornada.usuarioId,
                zonaId = jornada.zonaId,
                jornadaId = jornada.id,
                jornadaAbiertaEn = jornada.abiertaEn,
            )
        }
        sesionRepository.cerrar()
    }
}
