package pe.ecolecta.domain.usecase.vehiculo

import pe.ecolecta.domain.VehiculoInvalidoException
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.repository.VehiculoRepository

class ActualizarVehiculoUseCase(private val vehiculoRepository: VehiculoRepository) {
    suspend operator fun invoke(id: String, nombre: String, placa: String, activo: Boolean): Result<Unit> {
        val vehiculo = Vehiculo.crear(id, nombre, placa, activo).getOrElse { return Result.failure(it) }
        if (vehiculoRepository.existePlaca(vehiculo.placa, id)) {
            return Result.failure(VehiculoInvalidoException.PlacaDuplicada)
        }
        return runCatching { vehiculoRepository.actualizar(vehiculo) }
    }
}
