package pe.ecolecta.data.repository

import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.RutaProveedorCacheRepository

class SqlDelightRutaProveedorCacheRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : RutaProveedorCacheRepository {

    override suspend fun guardar(usuarioId: String, ubicacion: UbicacionAcopiador) = withContext(dispatcher) {
        db.rutaProveedorCacheQueries.guardar(
            usuario_id = usuarioId,
            zona_id = ubicacion.zonaId,
            zona_nombre = ubicacion.zonaNombre,
            acopiador_id = ubicacion.acopiadorId,
            acopiador_nombre = ubicacion.acopiadorNombre,
            vehiculo_id = ubicacion.vehiculoId,
            vehiculo_nombre = ubicacion.vehiculoNombre,
            jornada_id = ubicacion.jornadaId,
            jornada_abierta_en = ubicacion.jornadaAbiertaEn,
            fecha = ubicacion.fecha,
            lat = ubicacion.lat,
            lng = ubicacion.lng,
            precision_m = ubicacion.precisionM,
            capturada_en = ubicacion.capturadaEn,
            secuencia_en = ubicacion.secuenciaEn,
            seguimiento_activo = if (ubicacion.seguimientoActivo) 1L else 0L,
            jornada_abierta = if (ubicacion.jornadaAbierta) 1L else 0L,
        )
        Unit
    }

    override suspend fun obtener(usuarioId: String): UbicacionAcopiador? = withContext(dispatcher) {
        db.rutaProveedorCacheQueries.selectPorUsuario(usuarioId).executeAsOneOrNull()?.let { fila ->
            UbicacionAcopiador(
                zonaId = fila.zona_id,
                zonaNombre = fila.zona_nombre,
                acopiadorId = fila.acopiador_id,
                acopiadorNombre = fila.acopiador_nombre,
                vehiculoId = fila.vehiculo_id,
                vehiculoNombre = fila.vehiculo_nombre,
                jornadaId = fila.jornada_id,
                jornadaAbiertaEn = fila.jornada_abierta_en,
                fecha = fila.fecha,
                lat = fila.lat,
                lng = fila.lng,
                precisionM = fila.precision_m,
                capturadaEn = fila.capturada_en,
                secuenciaEn = fila.secuencia_en,
                seguimientoActivo = fila.seguimiento_activo != 0L,
                jornadaAbierta = fila.jornada_abierta != 0L,
            )
        }
    }

    override suspend fun eliminar(usuarioId: String) = withContext(dispatcher) {
        db.rutaProveedorCacheQueries.eliminar(usuarioId)
        Unit
    }
}
