package pe.ecolecta.domain.usecase.vehiculo

import pe.ecolecta.domain.VehiculoInvalidoException
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.VehiculoRepository

class CrearVehiculoUseCase(private val vehiculoRepository: VehiculoRepository) {
    suspend operator fun invoke(nombre: String, placa: String, activo: Boolean = true): Result<Vehiculo> {
        val vehiculo = Vehiculo.crear(nuevoId(), nombre, placa, activo).getOrElse { return Result.failure(it) }
        if (vehiculoRepository.existePlaca(vehiculo.placa, "")) {
            return Result.failure(VehiculoInvalidoException.PlacaDuplicada)
        }
        return runCatching {
            vehiculoRepository.insertar(vehiculo)
            vehiculo
        }
    }
}
