package pe.ecolecta.data.repository

import app.cash.sqldelight.coroutines.asFlow
import app.cash.sqldelight.coroutines.mapToList
import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.db.Registro_recibido as RegistroRecibidoFila
import pe.ecolecta.domain.acopio.RegistroAcopioCompartido
import pe.ecolecta.domain.acopio.TipoRegistroCompartido
import pe.ecolecta.domain.repository.RegistroRecibidoRepository

class SqlDelightRegistroRecibidoRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : RegistroRecibidoRepository {

    override fun observarPorCodigo(proveedorCodigo: String): Flow<List<RegistroAcopioCompartido>> =
        db.registroRecibidoQueries.selectPorCodigo(proveedorCodigo).asFlow().mapToList(dispatcher)
            .map { filas -> filas.mapNotNull { it.aDominio() } }

    override suspend fun guardar(registros: List<RegistroAcopioCompartido>, recibidoEn: Long) = withContext(dispatcher) {
        db.transaction {
            registros.forEach { r ->
                db.registroRecibidoQueries.insertarSiFalta(
                    id = r.id, tipo = r.tipo.name, proveedor_codigo = r.proveedorCodigo, zona_id = r.zonaId,
                    jornada_id = r.jornadaId, acopiador_id = r.acopiadorId, acopiador_nombre = r.acopiadorNombre,
                    fecha = r.fecha, registrado_en = r.registradoEn, litros = r.litros, tachos = r.tachos?.toLong(),
                    anulada = if (r.anulada) 1L else 0L, motivo = r.motivo, detalle = r.detalle,
                    deshecha = if (r.deshecha) 1L else 0L, actualizado_en = r.actualizadoEn, recibido_en = recibidoEn,
                )
                db.registroRecibidoQueries.actualizar(
                    tipo = r.tipo.name, proveedor_codigo = r.proveedorCodigo, zona_id = r.zonaId,
                    jornada_id = r.jornadaId, acopiador_id = r.acopiadorId, acopiador_nombre = r.acopiadorNombre,
                    fecha = r.fecha, registrado_en = r.registradoEn, litros = r.litros, tachos = r.tachos?.toLong(),
                    anulada = if (r.anulada) 1L else 0L, motivo = r.motivo, detalle = r.detalle,
                    deshecha = if (r.deshecha) 1L else 0L, actualizado_en = r.actualizadoEn, recibido_en = recibidoEn,
                    id = r.id,
                )
            }
        }
    }

    override suspend fun ultimaRecepcion(proveedorCodigo: String): Long? = withContext(dispatcher) {
        db.registroRecibidoQueries.ultimaRecepcion(proveedorCodigo).executeAsOneOrNull()?.ultima
    }
}

private fun RegistroRecibidoFila.aDominio(): RegistroAcopioCompartido? {
    val tipo = TipoRegistroCompartido.entries.firstOrNull { it.name == tipo } ?: return null
    return RegistroAcopioCompartido(
        id = id,
        tipo = tipo,
        proveedorCodigo = proveedor_codigo,
        zonaId = zona_id,
        jornadaId = jornada_id,
        acopiadorId = acopiador_id,
        acopiadorNombre = acopiador_nombre,
        fecha = fecha,
        registradoEn = registrado_en,
        litros = litros,
        tachos = tachos?.toInt(),
        anulada = anulada != 0L,
        motivo = motivo,
        detalle = detalle,
        deshecha = deshecha != 0L,
        actualizadoEn = actualizado_en,
    )
}
