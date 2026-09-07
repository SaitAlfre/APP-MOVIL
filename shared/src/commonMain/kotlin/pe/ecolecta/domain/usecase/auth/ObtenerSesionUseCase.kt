package pe.ecolecta.domain.usecase.auth

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.repository.SesionRepository

class ObtenerSesionUseCase(private val sesionRepository: SesionRepository) {
    operator fun invoke(): Flow<Sesion?> = sesionRepository.observar()
}
