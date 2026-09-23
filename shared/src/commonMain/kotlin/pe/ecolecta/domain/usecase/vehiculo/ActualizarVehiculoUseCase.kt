package pe.ecolecta.domain.usecase.vehiculo

import pe.ecolecta.domain.VehiculoInvalidoException
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.VehiculoRepository

class ActualizarVehiculoUseCase(private val vehiculoRepository: VehiculoRepository, private val cuentas: CuentasRepository) {
    suspend operator fun invoke(id: String, nombre: String, placa: String, activo: Boolean): Result<Unit> {
        val vehiculo = Vehiculo.crear(id, nombre, placa, activo).getOrElse { return Result.failure(it) }
        if (vehiculoRepository.existePlaca(vehiculo.placa, id)) {
            return Result.failure(VehiculoInvalidoException.PlacaDuplicada)
        }
        val anterior = vehiculoRepository.obtenerPorId(id) ?: return Result.failure(IllegalArgumentException("El vehículo ya no existe."))
        if (anterior.activo && !activo && cuentas.vehiculoEnJornadaAbierta(id)) {
            return Result.failure(IllegalArgumentException("No se puede desactivar: el vehículo está en una jornada abierta."))
        }
        return runCatching { vehiculoRepository.actualizar(vehiculo) }
    }
}
