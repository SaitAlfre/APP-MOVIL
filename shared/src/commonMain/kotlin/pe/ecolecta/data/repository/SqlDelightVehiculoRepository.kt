package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.repository.VehiculoRepository

class SqlDelightVehiculoRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : VehiculoRepository {

    override fun observarTodos(): Flow<List<Vehiculo>> =
        db.vehiculoQueries.selectTodos().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarActivos(): Flow<List<Vehiculo>> =
        db.vehiculoQueries.selectActivos().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): Vehiculo? = withContext(dispatcher) {
        db.vehiculoQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun existePlaca(placa: String, idExcluido: String): Boolean = withContext(dispatcher) {
        db.vehiculoQueries.existePlaca(placa = placa, idExcluido = idExcluido).executeAsOne() > 0
    }

    override suspend fun insertar(vehiculo: Vehiculo) = withContext(dispatcher) {
        db.vehiculoQueries.insertar(id = vehiculo.id, nombre = vehiculo.nombre, placa = vehiculo.placa, activo = if (vehiculo.activo) 1 else 0)
        Unit
    }

    override suspend fun actualizar(vehiculo: Vehiculo) = withContext(dispatcher) {
        db.vehiculoQueries.actualizar(nombre = vehiculo.nombre, placa = vehiculo.placa, activo = if (vehiculo.activo) 1 else 0, id = vehiculo.id)
        Unit
    }

    override suspend fun desactivar(id: String) = withContext(dispatcher) {
        db.vehiculoQueries.desactivar(id)
        Unit
    }
}
