package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Zona

interface ZonaRepository {
    fun observarTodas(): Flow<List<Zona>>
    fun observarActivas(): Flow<List<Zona>>
    suspend fun obtenerPorId(id: String): Zona?
    suspend fun insertar(zona: Zona)
    suspend fun actualizar(zona: Zona)
    suspend fun desactivar(id: String)
    suspend fun contarProveedoresEnZona(id: String): Long
}
