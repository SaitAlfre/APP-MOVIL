package pe.ecolecta.domain.usecase.seguimiento

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.repository.EstadoSeguimientoRepository

class ObtenerEstadoSeguimientoUseCase(private val repository: EstadoSeguimientoRepository) {
    operator fun invoke(): Flow<EstadoSeguimiento> = repository.observar()
}
