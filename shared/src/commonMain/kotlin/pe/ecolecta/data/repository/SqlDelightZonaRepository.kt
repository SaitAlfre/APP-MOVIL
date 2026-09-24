package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.data.local.EntidadCambio
import pe.ecolecta.data.local.marcarCambio

class SqlDelightZonaRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : ZonaRepository {

    override fun observarTodas(): Flow<List<Zona>> =
        db.zonaQueries.selectTodas().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override fun observarActivas(): Flow<List<Zona>> =
        db.zonaQueries.selectActivas().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): Zona? = withContext(dispatcher) {
        db.zonaQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun insertar(zona: Zona) = withContext(dispatcher) {
        db.zonaQueries.insertar(id = zona.id, nombre = zona.nombre, activo = if (zona.activo) 1 else 0)
        db.marcarCambio(EntidadCambio.ZONA, zona.id)
        Unit
    }

    override suspend fun actualizar(zona: Zona) = withContext(dispatcher) {
        db.zonaQueries.actualizar(nombre = zona.nombre, activo = if (zona.activo) 1 else 0, id = zona.id)
        db.marcarCambio(EntidadCambio.ZONA, zona.id)
        Unit
    }

    override suspend fun desactivar(id: String) = withContext(dispatcher) {
        db.zonaQueries.desactivar(id)
        db.marcarCambio(EntidadCambio.ZONA, id)
        Unit
    }

    override suspend fun contarProveedoresEnZona(id: String): Long = withContext(dispatcher) {
        db.zonaQueries.contarProveedoresEnZona(id).executeAsOne()
    }
}
