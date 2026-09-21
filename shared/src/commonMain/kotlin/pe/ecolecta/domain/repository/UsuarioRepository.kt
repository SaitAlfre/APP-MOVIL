package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.security.ParHashPin

/** [idExcluido] pásese vacío ("") al validar unicidad durante la creación, donde aún no hay id propio. */
interface UsuarioRepository {
    fun observarTodos(): Flow<List<Usuario>>
    suspend fun obtenerPorId(id: String): Usuario?
    suspend fun obtenerPorUsername(username: String): Usuario?
    suspend fun existeUsername(username: String, idExcluido: String): Boolean
    suspend fun existeDni(dni: String, idExcluido: String): Boolean
    suspend fun insertar(usuario: Usuario)
    suspend fun actualizar(id: String, nombres: String, dni: String, activo: Boolean, updatedAt: Long)
    suspend fun actualizarPin(id: String, pinHash: String, pinSalt: String, updatedAt: Long)

    /**
     * Igual que encadenar [actualizar] + [asignarRol]/[quitarRol] por cada rol que cambió + (si
     * corresponde) [actualizarPin], pero todo en una única transacción: si cualquier paso falla, no
     * queda un usuario a medio actualizar (p. ej. con el nombre nuevo pero el rol viejo).
     */
    suspend fun actualizarCompleto(
        id: String,
        nombres: String,
        dni: String,
        activo: Boolean,
        rolesAgregados: List<Rol>,
        rolesQuitados: List<Rol>,
        nuevoPin: ParHashPin?,
        updatedAt: Long,
    )
    suspend fun desactivar(id: String, updatedAt: Long)
    suspend fun asignarRol(usuarioId: String, rol: Rol)
    suspend fun quitarRol(usuarioId: String, rol: Rol)
    suspend fun contarUsuariosConRol(rol: Rol): Long
    suspend fun registrarIntentoFallido(id: String, updatedAt: Long)
    suspend fun bloquear(id: String, bloqueadoHasta: Long, updatedAt: Long)
    suspend fun resetIntentos(id: String, updatedAt: Long)
}
