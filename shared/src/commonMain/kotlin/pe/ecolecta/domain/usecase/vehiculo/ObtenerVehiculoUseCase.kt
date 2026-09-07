package pe.ecolecta.domain.usecase.vehiculo

import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.repository.VehiculoRepository

class ObtenerVehiculoUseCase(private val vehiculoRepository: VehiculoRepository) {
    suspend operator fun invoke(id: String): Vehiculo? = vehiculoRepository.obtenerPorId(id)
}
