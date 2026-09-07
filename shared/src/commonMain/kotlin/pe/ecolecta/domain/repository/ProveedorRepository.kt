package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor

interface ProveedorRepository {
    fun observarTodos(): Flow<List<Proveedor>>
    fun observarPorZona(zonaId: String): Flow<List<Proveedor>>
    fun observarActivosPorZona(zonaId: String): Flow<List<Proveedor>>
    suspend fun obtenerPorId(id: String): Proveedor?

    /** Único punto de entrada para el módulo PROVEEDOR: resuelve el proveedor a partir del usuario autenticado (§5, §39). */
    suspend fun obtenerPorUsuarioId(usuarioId: String): Proveedor?
    suspend fun vincularUsuario(proveedorId: String, usuarioId: String)
    suspend fun existeCodigo(codigo: String, idExcluido: String): Boolean
    suspend fun existeDni(dni: String, idExcluido: String): Boolean
    suspend fun insertar(proveedor: Proveedor)
    suspend fun actualizar(proveedor: Proveedor)
    suspend fun cambiarEstado(id: String, estado: EstadoProveedor, updatedAt: Long)
    suspend fun cambiarZona(id: String, zonaId: String, updatedAt: Long)
    suspend fun contarActivos(): Long
}
