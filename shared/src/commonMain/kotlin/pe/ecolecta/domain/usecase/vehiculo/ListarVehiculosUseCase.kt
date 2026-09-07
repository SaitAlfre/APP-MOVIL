package pe.ecolecta.domain.usecase.vehiculo

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.repository.VehiculoRepository

class ListarVehiculosUseCase(private val vehiculoRepository: VehiculoRepository) {
    operator fun invoke(soloActivos: Boolean = false): Flow<List<Vehiculo>> =
        if (soloActivos) vehiculoRepository.observarActivos() else vehiculoRepository.observarTodos()
}
