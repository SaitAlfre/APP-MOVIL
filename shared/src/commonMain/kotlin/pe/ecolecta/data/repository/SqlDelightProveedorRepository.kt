package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

class SqlDelightProveedorRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : ProveedorRepository {

    override fun observarTodos(): Flow<List<Proveedor>> =
        db.proveedorQueries.selectTodos().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarPorZona(zonaId: String): Flow<List<Proveedor>> =
        db.proveedorQueries.selectPorZona(zonaId).asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarActivosPorZona(zonaId: String): Flow<List<Proveedor>> =
        db.proveedorQueries.selectActivosPorZona(zonaId).asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): Proveedor? = withContext(dispatcher) {
        db.proveedorQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun obtenerPorUsuarioId(usuarioId: String): Proveedor? = withContext(dispatcher) {
        db.proveedorQueries.selectPorUsuarioId(usuarioId).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun vincularUsuario(proveedorId: String, usuarioId: String) = withContext(dispatcher) {
        db.proveedorQueries.vincularUsuario(usuario_id = usuarioId, id = proveedorId)
        Unit
    }

    override suspend fun existeCodigo(codigo: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.proveedorQueries.existeCodigo(codigo = codigo, idExcluido = idExcluido).executeAsOne() > 0
    }

    override suspend fun existeDni(dni: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.proveedorQueries.existeDni(dni = dni, idExcluido = idExcluido).executeAsOne() > 0
    }

    override suspend fun insertar(proveedor: Proveedor) = withContext(dispatcher) {
        db.transaction {
        db.proveedorQueries.insertar(
            id = proveedor.id,
            codigo = proveedor.codigo,
            nombres = proveedor.nombres,
            dni = proveedor.dni,
            telefono = proveedor.telefono,
            direccion = proveedor.direccion,
            zona_id = proveedor.zonaId,
            tachos = proveedor.tachos.toLong(),
            capacidad_tacho_l = proveedor.capacidadTachoL,
            estado = proveedor.estado.name,
            updated_at = proveedor.updatedAt,
            sync_state = proveedor.syncState.name,
        )
        db.proveedorQueries.actualizarResponsable(proveedor.dueno, proveedor.id)
        }
        Unit
    }

    override suspend fun actualizar(proveedor: Proveedor) = withContext(dispatcher) {
        db.transaction {
        db.proveedorQueries.actualizar(
            nombres = proveedor.nombres,
            dni = proveedor.dni,
            telefono = proveedor.telefono,
            direccion = proveedor.direccion,
            zona_id = proveedor.zonaId,
            tachos = proveedor.tachos.toLong(),
            capacidad_tacho_l = proveedor.capacidadTachoL,
            updated_at = proveedor.updatedAt,
            id = proveedor.id,
        )
        db.proveedorQueries.actualizarResponsable(proveedor.dueno, proveedor.id)
        db.proveedorQueries.cambiarEstado(proveedor.estado.name, proveedor.updatedAt, proveedor.id)
        }
        Unit
    }

    override suspend fun cambiarEstado(id: String, estado: EstadoProveedor, updatedAt: Long) = withContext(dispatcher) {
        db.proveedorQueries.cambiarEstado(estado = estado.name, updated_at = updatedAt, id = id)
        Unit
    }

    override suspend fun cambiarZona(id: String, zonaId: String, updatedAt: Long) = withContext(dispatcher) {
        val actual = db.proveedorQueries.selectPorId(id).executeAsOne()
        db.proveedorQueries.actualizar(
            nombres = actual.nombres,
            dni = actual.dni,
            telefono = actual.telefono,
            direccion = actual.direccion,
            zona_id = zonaId,
            tachos = actual.tachos,
            capacidad_tacho_l = actual.capacidad_tacho_l,
            updated_at = updatedAt,
            id = id,
        )
        Unit
    }

    override suspend fun contarActivos(): Long = withContext(dispatcher) {
        db.proveedorQueries.contarActivos().executeAsOne()
    }
}
