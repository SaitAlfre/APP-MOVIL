package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.local.aDominio
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaRepository

class SqlDelightJornadaRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : JornadaRepository {

    override fun observarTodas(): Flow<List<Jornada>> =
        db.jornadaQueries.selectTodas().asFlow().mapToList(dispatcher).map { filas -> filas.map { it.aDominio() } }

    override suspend fun obtenerPorId(id: String): Jornada? = withContext(dispatcher) {
        db.jornadaQueries.selectPorId(id).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun obtenerPorUsuarioYFecha(usuarioId: String, fecha: LocalDate): Jornada? = withContext(dispatcher) {
        db.jornadaQueries.selectPorUsuarioYFecha(usuario_id = usuarioId, fecha = fecha.toString()).executeAsOneOrNull()?.aDominio()
    }

    override suspend fun insertar(jornada: Jornada) = withContext(dispatcher) {
        db.jornadaQueries.insertar(
            id = jornada.id,
            usuario_id = jornada.usuarioId,
            zona_id = jornada.zonaId,
            vehiculo_id = jornada.vehiculoId,
            fecha = jornada.fecha.toString(),
            abierta_en = jornada.abiertaEn,
            sync_state = jornada.syncState.name,
        )
        Unit
    }

    override suspend fun cerrar(id: String, cerradaEn: Long) = withContext(dispatcher) {
        db.jornadaQueries.cerrar(cerrada_en = cerradaEn, id = id)
        Unit
    }

    override suspend fun filtrar(
        fecha: LocalDate?,
        usuarioId: String?,
        zonaId: String?,
        vehiculoId: String?,
    ): List<Jornada> = withContext(dispatcher) {
        db.jornadaQueries.selectConFiltros(
            fecha = fecha?.toString(),
            usuario_id = usuarioId,
            zona_id = zonaId,
            vehiculo_id = vehiculoId,
        ).executeAsList().map { it.aDominio() }
    }

    override suspend fun contarAbiertas(): Long = withContext(dispatcher) {
        db.jornadaQueries.contarAbiertas().executeAsOne()
    }
}
