package pe.ecolecta.domain.usecase.auth

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.repository.ServidorWebRepository

/**
 * Cierra la sesión local. Una jornada abierta NO se cierra: el acopiador la recupera al volver a
 * entrar (solo [pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase] la finaliza). Los registros
 * pendientes de sincronizar se conservan. Si se retira el token, vuelven a enviarse al autenticarse.
 */
class CerrarSesionUseCase(
    private val sesionRepository: SesionRepository,
    private val servidor: ServidorWebRepository? = null,
) {
    suspend operator fun invoke() {
        val usuarioId = sesionRepository.observar().first()?.usuario?.id
        sesionRepository.cerrar()
        if (usuarioId != null) {
            try {
                servidor?.desvincular(usuarioId)
            } catch (cancelada: CancellationException) {
                throw cancelada
            } catch (_: Exception) {
                // La sesión local ya está cerrada. Sin red no se afirma revocación en el servidor.
            }
        }
    }
}
