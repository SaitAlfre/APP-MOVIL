package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToOneOrNull
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.repository.SesionRepository
import pe.ecolecta.domain.repository.UsuarioRepository

/**
 * Persiste la sesión activa en la base de datos local (tabla `sesion_activa`), a diferencia de
 * [pe.ecolecta.data.security.InMemorySesionRepository] que se pierde al matar el proceso. Solo
 * guarda `usuario_id`/`rol_activo`: los datos del usuario siempre se leen frescos de [usuarioRepository]
 * en cada emisión, así un cambio hecho por ADMIN mientras el dispositivo estaba offline (p. ej.
 * desactivar la cuenta o quitarle un rol) se refleja apenas vuelve a haber conexión/consulta local.
 */
class SqlDelightSesionRepository(
    private val db: EcolectaDatabase,
    private val usuarioRepository: UsuarioRepository,
    private val dispatcher: CoroutineDispatcher,
) : SesionRepository {

    override fun observar(): Flow<Sesion?> =
        db.sesionQueries.obtener().asFlow().mapToOneOrNull(dispatcher).map { fila ->
            val usuario = fila?.let { usuarioRepository.obtenerPorId(it.usuario_id) } ?: return@map null
            Sesion(usuario = usuario, rolActivo = Rol.desde(fila.rol_activo) ?: return@map null)
        }

    override suspend fun iniciar(sesion: Sesion) = withContext(dispatcher) {
        db.sesionQueries.establecer(usuario_id = sesion.usuario.id, rol_activo = sesion.rolActivo.name)
        Unit
    }

    override suspend fun cerrar() = withContext(dispatcher) {
        db.sesionQueries.limpiar()
        Unit
    }
}
