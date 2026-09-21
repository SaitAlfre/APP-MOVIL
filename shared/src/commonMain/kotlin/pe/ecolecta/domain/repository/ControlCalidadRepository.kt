package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.ControlCalidad

interface ControlCalidadRepository {
    fun observarTodos(): Flow<List<ControlCalidad>>
    fun observarPorUsuario(usuarioId: String): Flow<List<ControlCalidad>>
    suspend fun obtenerPorId(id: String): ControlCalidad?
    suspend fun existeCodigoMuestra(codigo: String): Boolean
    suspend fun insertar(control: ControlCalidad)
    suspend fun obtenerZonaAsignada(usuarioId: String): String?
}

