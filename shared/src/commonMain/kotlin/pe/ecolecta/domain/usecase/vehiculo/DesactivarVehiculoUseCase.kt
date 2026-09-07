package pe.ecolecta.domain.usecase.vehiculo

import pe.ecolecta.domain.repository.VehiculoRepository

class DesactivarVehiculoUseCase(private val vehiculoRepository: VehiculoRepository) {
    suspend operator fun invoke(id: String): Result<Unit> = runCatching {
        vehiculoRepository.desactivar(id)
    }
}
