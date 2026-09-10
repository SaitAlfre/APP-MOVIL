package pe.ecolecta.data.repository

import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.repository.UbicacionAcopiadorLocalRepository
import pe.ecolecta.domain.repository.UbicacionLocalAcopiador

class SqlDelightUbicacionAcopiadorLocalRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : UbicacionAcopiadorLocalRepository {

    override suspend fun guardar(
        usuarioId: String,
        jornadaId: String,
        zonaId: String,
        lat: Double,
        lng: Double,
        precisionM: Double,
        capturadaEn: Long,
        publicada: Boolean,
    ) = withContext(dispatcher) {
        db.ubicacionAcopiadorLocalQueries.guardar(
            usuario_id = usuarioId,
            jornada_id = jornadaId,
            zona_id = zonaId,
            lat = lat,
            lng = lng,
            precision_m = precisionM,
            capturada_en = capturadaEn,
            publicada = if (publicada) 1L else 0L,
        )
        Unit
    }

    override suspend fun marcarPublicada(usuarioId: String) = withContext(dispatcher) {
        db.ubicacionAcopiadorLocalQueries.marcarPublicada(usuarioId)
        Unit
    }

    override suspend fun obtener(usuarioId: String): UbicacionLocalAcopiador? = withContext(dispatcher) {
        db.ubicacionAcopiadorLocalQueries.selectPorUsuario(usuarioId).executeAsOneOrNull()?.let { fila ->
            UbicacionLocalAcopiador(
                usuarioId = fila.usuario_id,
                jornadaId = fila.jornada_id,
                zonaId = fila.zona_id,
                lat = fila.lat,
                lng = fila.lng,
                precisionM = fila.precision_m,
                capturadaEn = fila.capturada_en,
                publicada = fila.publicada != 0L,
            )
        }
    }

    override suspend fun eliminar(usuarioId: String) = withContext(dispatcher) {
        db.ubicacionAcopiadorLocalQueries.eliminar(usuarioId)
        Unit
    }
}
