package pe.ecolecta.domain.usecase.auth

import pe.ecolecta.domain.repository.SesionRepository

/**
 * Cierra la sesión local. Una jornada abierta NO se cierra: el acopiador la recupera al volver a
 * entrar (solo [pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase] la finaliza). Los registros
 * pendientes de sincronizar se conservan y se siguen enviando.
 */
class CerrarSesionUseCase(
    private val sesionRepository: SesionRepository,
) {
    suspend operator fun invoke() {
        sesionRepository.cerrar()
    }
}
