package pe.ecolecta.domain.usecase.auth

import pe.ecolecta.domain.repository.SesionRepository

class CerrarSesionUseCase(private val sesionRepository: SesionRepository) {
    suspend operator fun invoke() {
        sesionRepository.cerrar()
    }
}
