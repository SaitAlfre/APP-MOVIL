package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Vehiculo

interface VehiculoRepository {
    fun observarTodos(): Flow<List<Vehiculo>>
    fun observarActivos(): Flow<List<Vehiculo>>
    suspend fun obtenerPorId(id: String): Vehiculo?
    suspend fun existePlaca(placa: String, idExcluido: String): Boolean
    suspend fun insertar(vehiculo: Vehiculo)
    suspend fun actualizar(vehiculo: Vehiculo)
    suspend fun desactivar(id: String)
}
