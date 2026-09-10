package pe.ecolecta.domain.usecase.seguimiento

import pe.ecolecta.domain.SeguimientoController
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.repository.EstadoSeguimientoRepository

/**
 * Detiene el seguimiento y publica `seguimientoActivo=false` en Firestore. A propósito NUNCA toca
 * `jornadaAbierta`: conservar el estado real de la jornada es responsabilidad exclusiva de
 * [pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase] — así "detener seguimiento" (botón manual
 * o cerrar sesión) nunca finaliza la jornada por error.
 */
class DetenerSeguimientoUseCase(
    private val seguimientoController: SeguimientoController,
    private val estadoSeguimientoRepository: EstadoSeguimientoRepository,
    private val publicarEstadoRemotoUseCase: PublicarEstadoRemotoUseCase,
) {
    suspend operator fun invoke(usuarioId: String, zonaId: String, jornadaId: String, jornadaAbiertaEn: Long) {
        seguimientoController.detener()
        estadoSeguimientoRepository.actualizar(EstadoSeguimiento.INACTIVO)
        publicarEstadoRemotoUseCase(
            usuarioId = usuarioId,
            zonaId = zonaId,
            jornadaId = jornadaId,
            jornadaAbiertaEn = jornadaAbiertaEn,
            jornadaAbierta = null,
        )
    }
}
