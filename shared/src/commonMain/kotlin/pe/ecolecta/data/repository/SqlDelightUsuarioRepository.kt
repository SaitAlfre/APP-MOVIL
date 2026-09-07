package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOn
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.UsuarioRepository

class SqlDelightUsuarioRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : UsuarioRepository {

    override fun observarTodos(): Flow<List<Usuario>> =
        db.usuarioQueries.selectTodos().asFlow()
            .mapToList(dispatcher)
            .map { filas -> filas.map { it.aDominio(rolesDe(it.id)) } }
            .flowOn(dispatcher)

    override suspend fun obtenerPorId(id: String): Usuario? = withContext(dispatcher) {
        db.usuarioQueries.selectPorId(id).executeAsOneOrNull()?.let { it.aDominio(rolesDe(it.id)) }
    }

    override suspend fun obtenerPorUsername(username: String): Usuario? = withContext(dispatcher) {
        db.usuarioQueries.selectPorUsername(username).executeAsOneOrNull()?.let { it.aDominio(rolesDe(it.id)) }
    }

    override suspend fun existeUsername(username: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.usuarioQueries.existeUsername(username = username, idExcluido = idExcluido).executeAsOne() > 0
    }

    override suspend fun existeDni(dni: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.usuarioQueries.existeDni(dni = dni, idExcluido = idExcluido).executeAsOne() > 0
    }

    override suspend fun insertar(usuario: Usuario) = withContext(dispatcher) {
        db.usuarioQueries.transaction {
            db.usuarioQueries.insertar(
                id = usuario.id,
                username = usuario.username,
                nombres = usuario.nombres,
                dni = usuario.dni,
                pin_hash = usuario.pinHash,
                pin_salt = usuario.pinSalt,
                activo = if (usuario.activo) 1 else 0,
                updated_at = usuario.updatedAt,
            )
            usuario.roles.forEach { rol -> db.usuarioRolQueries.insertar(usuario_id = usuario.id, rol = rol.name) }
        }
    }

    override suspend fun actualizar(id: String, nombres: String, dni: String, activo: Boolean, updatedAt: Long) = withContext(dispatcher) {
        db.usuarioQueries.actualizar(nombres = nombres, dni = dni, activo = if (activo) 1 else 0, updated_at = updatedAt, id = id)
        Unit
    }

    override suspend fun actualizarPin(id: String, pinHash: String, pinSalt: String, updatedAt: Long) = withContext(dispatcher) {
        db.usuarioQueries.actualizarPin(pin_hash = pinHash, pin_salt = pinSalt, updated_at = updatedAt, id = id)
        Unit
    }

    override suspend fun desactivar(id: String, updatedAt: Long) = withContext(dispatcher) {
        db.usuarioQueries.desactivar(updated_at = updatedAt, id = id)
        Unit
    }

    override suspend fun asignarRol(usuarioId: String, rol: Rol) = withContext(dispatcher) {
        db.usuarioRolQueries.insertar(usuario_id = usuarioId, rol = rol.name)
        Unit
    }

    override suspend fun quitarRol(usuarioId: String, rol: Rol) = withContext(dispatcher) {
        db.usuarioRolQueries.eliminar(usuario_id = usuarioId, rol = rol.name)
        Unit
    }

    override suspend fun contarUsuariosConRol(rol: Rol): Long = withContext(dispatcher) {
        db.usuarioRolQueries.contarUsuariosConRol(rol.name).executeAsOne()
    }

    override suspend fun registrarIntentoFallido(id: String, updatedAt: Long) = withContext(dispatcher) {
        db.usuarioQueries.registrarIntentoFallido(updated_at = updatedAt, id = id)
        Unit
    }

    override suspend fun bloquear(id: String, bloqueadoHasta: Long, updatedAt: Long) = withContext(dispatcher) {
        db.usuarioQueries.bloquear(bloqueado_hasta = bloqueadoHasta, updated_at = updatedAt, id = id)
        Unit
    }

    override suspend fun resetIntentos(id: String, updatedAt: Long) = withContext(dispatcher) {
        db.usuarioQueries.resetIntentos(updated_at = updatedAt, id = id)
        Unit
    }

    private fun rolesDe(usuarioId: String): List<Rol> =
        db.usuarioRolQueries.selectPorUsuario(usuarioId).executeAsList().map { Rol.valueOf(it) }
}
