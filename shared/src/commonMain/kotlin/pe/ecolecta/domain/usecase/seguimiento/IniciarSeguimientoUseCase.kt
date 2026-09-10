package pe.ecolecta.domain.usecase.seguimiento

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.SeguimientoController
import pe.ecolecta.domain.excepcionAEstadoSeguimiento
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.EstadoSeguimientoRepository
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.SesionRepository

/** Solo permite iniciar el seguimiento con sesión ACOPIADOR y jornada abierta. */
class IniciarSeguimientoUseCase(
    private val sesionRepository: SesionRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val seguimientoController: SeguimientoController,
    private val estadoSeguimientoRepository: EstadoSeguimientoRepository,
) {
    suspend operator fun invoke(): Result<Unit> = runCatching {
        val sesion = sesionRepository.observar().first() ?: error("No hay sesión activa.")
        check(sesion.rolActivo == Rol.ACOPIADOR) { "Solo un ACOPIADOR puede iniciar el seguimiento." }
        val jornada = jornadaEnCursoRepository.observar().first() ?: error("No hay una jornada abierta.")
        check(jornada.estaAbierta) { "La jornada debe estar abierta para iniciar el seguimiento." }

        // Aún no hay coordenada real: "Activo" solo lo pone el propio flujo de ubicación al capturar.
        estadoSeguimientoRepository.actualizar(EstadoSeguimiento.BUSCANDO)
        seguimientoController.iniciar(sesion.usuario.id, jornada.id, jornada.zonaId)
    }.onFailure { error ->
        if (error is CancellationException) throw error
        estadoSeguimientoRepository.actualizar(excepcionAEstadoSeguimiento(error))
    }
}
