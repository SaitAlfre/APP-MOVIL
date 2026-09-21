package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.ParHashPin

class FakeUsuarioRepository : UsuarioRepository {
    private val usuarios = MutableStateFlow<List<Usuario>>(emptyList())

    override fun observarTodos(): Flow<List<Usuario>> = usuarios.asStateFlow()

    override suspend fun obtenerPorId(id: String): Usuario? = usuarios.value.firstOrNull { it.id == id }

    override suspend fun obtenerPorUsername(username: String): Usuario? = usuarios.value.firstOrNull { it.username == username }

    override suspend fun existeUsername(username: String, idExcluido: String): Boolean =
        usuarios.value.any { it.username == username && it.id != idExcluido }

    override suspend fun existeDni(dni: String, idExcluido: String): Boolean =
        usuarios.value.any { it.dni == dni && it.id != idExcluido }

    override suspend fun insertar(usuario: Usuario) {
        usuarios.value = usuarios.value + usuario
    }

    override suspend fun actualizar(id: String, nombres: String, dni: String, activo: Boolean, updatedAt: Long) {
        reemplazar(id) { it.copy(nombres = nombres, dni = dni, activo = activo, updatedAt = updatedAt) }
    }

    override suspend fun actualizarPin(id: String, pinHash: String, pinSalt: String, updatedAt: Long) {
        reemplazar(id) { it.copy(pinHash = pinHash, pinSalt = pinSalt, updatedAt = updatedAt) }
    }

    override suspend fun desactivar(id: String, updatedAt: Long) {
        reemplazar(id) { it.copy(activo = false, updatedAt = updatedAt) }
    }

    override suspend fun asignarRol(usuarioId: String, rol: Rol) {
        reemplazar(usuarioId) { it.copy(roles = (it.roles + rol).distinct()) }
    }

    override suspend fun quitarRol(usuarioId: String, rol: Rol) {
        reemplazar(usuarioId) { it.copy(roles = it.roles - rol) }
    }

    override suspend fun actualizarCompleto(
        id: String,
        nombres: String,
        dni: String,
        activo: Boolean,
        rolesAgregados: List<Rol>,
        rolesQuitados: List<Rol>,
        nuevoPin: ParHashPin?,
        updatedAt: Long,
    ) {
        reemplazar(id) { usuario ->
            usuario.copy(
                nombres = nombres,
                dni = dni,
                activo = activo,
                roles = (usuario.roles + rolesAgregados - rolesQuitados.toSet()).distinct(),
                pinHash = nuevoPin?.hash ?: usuario.pinHash,
                pinSalt = nuevoPin?.salt ?: usuario.pinSalt,
                updatedAt = updatedAt,
            )
        }
    }

    override suspend fun contarUsuariosConRol(rol: Rol): Long = usuarios.value.count { it.activo && rol in it.roles }.toLong()

    override suspend fun registrarIntentoFallido(id: String, updatedAt: Long) {
        reemplazar(id) { it.copy(intentosFallidos = it.intentosFallidos + 1, updatedAt = updatedAt) }
    }

    override suspend fun bloquear(id: String, bloqueadoHasta: Long, updatedAt: Long) {
        reemplazar(id) { it.copy(bloqueadoHasta = bloqueadoHasta, updatedAt = updatedAt) }
    }

    override suspend fun resetIntentos(id: String, updatedAt: Long) {
        reemplazar(id) { it.copy(intentosFallidos = 0, bloqueadoHasta = null, updatedAt = updatedAt) }
    }

    private fun reemplazar(id: String, transformar: (Usuario) -> Usuario) {
        usuarios.value = usuarios.value.map { if (it.id == id) transformar(it) else it }
    }
}
