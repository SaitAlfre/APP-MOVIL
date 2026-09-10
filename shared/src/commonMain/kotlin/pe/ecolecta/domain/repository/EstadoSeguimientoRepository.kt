package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.EstadoSeguimiento

/** Puntero en memoria al estado actual del seguimiento de ubicación (independiente de la persistencia). */
interface EstadoSeguimientoRepository {
    fun observar(): Flow<EstadoSeguimiento>
    suspend fun actualizar(estado: EstadoSeguimiento)
}
