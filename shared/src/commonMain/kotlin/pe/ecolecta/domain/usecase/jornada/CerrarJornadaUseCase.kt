package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.SeguimientoController
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.repository.EstadoSeguimientoRepository
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.usecase.seguimiento.PublicarEstadoRemotoUseCase

/**
 * El seguimiento de ubicación solo debe existir durante una jornada abierta: cerrarla lo detiene.
 * Si el cierre en sí falla, la jornada queda abierta y el seguimiento no se toca (§ requisito: "si el
 * cierre falla, conserva la jornada abierta"). Si el cierre tiene éxito pero detener el seguimiento
 * falla (poco probable: solo actualiza estado en memoria y envía un Intent local), la jornada ya quedó
 * cerrada de verdad y no se revierte por eso.
 *
 * Este es el ÚNICO caso de uso autorizado a publicar `jornadaAbierta=false` en Firestore — nunca
 * "detener seguimiento" ni "cerrar sesión" (ver [pe.ecolecta.domain.usecase.seguimiento.DetenerSeguimientoUseCase]).
 */
class CerrarJornadaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val reloj: Reloj,
    private val seguimientoController: SeguimientoController,
    private val estadoSeguimientoRepository: EstadoSeguimientoRepository,
    private val publicarEstadoRemotoUseCase: PublicarEstadoRemotoUseCase,
) {
    suspend operator fun invoke(jornadaId: String): Result<Unit> = runCatching {
        val jornada = jornadaRepository.obtenerPorId(jornadaId) ?: error("La jornada no existe.")
        jornadaRepository.cerrar(jornadaId, reloj.ahora().toEpochMilliseconds())
        jornadaEnCursoRepository.limpiar()
        jornada
    }.also { resultado ->
        val jornada = resultado.getOrNull()
        if (jornada != null) {
            seguimientoController.detener()
            runCatching { estadoSeguimientoRepository.actualizar(EstadoSeguimiento.INACTIVO) }
            runCatching {
                publicarEstadoRemotoUseCase(
                    usuarioId = jornada.usuarioId,
                    zonaId = jornada.zonaId,
                    jornadaId = jornada.id,
                    jornadaAbiertaEn = jornada.abiertaEn,
                    jornadaAbierta = false,
                )
            }
        }
    }.map { }
}
