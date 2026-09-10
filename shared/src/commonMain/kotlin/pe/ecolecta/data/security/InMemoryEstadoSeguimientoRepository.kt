package pe.ecolecta.data.security

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.repository.EstadoSeguimientoRepository

class InMemoryEstadoSeguimientoRepository : EstadoSeguimientoRepository {
    private val estado = MutableStateFlow(EstadoSeguimiento.INACTIVO)

    override fun observar(): Flow<EstadoSeguimiento> = estado.asStateFlow()

    override suspend fun actualizar(estado: EstadoSeguimiento) {
        this.estado.value = estado
    }
}
