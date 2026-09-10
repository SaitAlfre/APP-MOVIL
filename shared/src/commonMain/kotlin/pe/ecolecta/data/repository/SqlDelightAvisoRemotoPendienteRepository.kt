package pe.ecolecta.data.repository

import kotlinx.coroutines.CoroutineDispatcher
import kotlinx.coroutines.withContext
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.domain.model.AvisoRemotoPendiente
import pe.ecolecta.domain.repository.AvisoRemotoPendienteRepository

class SqlDelightAvisoRemotoPendienteRepository(
    private val db: EcolectaDatabase,
    private val dispatcher: CoroutineDispatcher,
) : AvisoRemotoPendienteRepository {

    override suspend fun guardar(aviso: AvisoRemotoPendiente) = withContext(dispatcher) {
        db.avisoRemotoPendienteQueries.guardar(
            usuario_id = aviso.usuarioId,
            zona_id = aviso.zonaId,
            jornada_id = aviso.jornadaId,
            jornada_abierta_en = aviso.jornadaAbiertaEn,
            secuencia_en = aviso.secuenciaEn,
            jornada_abierta = aviso.jornadaAbierta?.let { if (it) 1L else 0L },
            creado_en = aviso.creadoEn,
        )
        Unit
    }

    override suspend fun obtener(usuarioId: String): AvisoRemotoPendiente? = withContext(dispatcher) {
        db.avisoRemotoPendienteQueries.selectPorUsuario(usuarioId).executeAsOneOrNull()?.let { fila ->
            AvisoRemotoPendiente(
                usuarioId = fila.usuario_id,
                zonaId = fila.zona_id,
                jornadaId = fila.jornada_id,
                jornadaAbiertaEn = fila.jornada_abierta_en,
                secuenciaEn = fila.secuencia_en,
                jornadaAbierta = fila.jornada_abierta?.let { it != 0L },
                creadoEn = fila.creado_en,
            )
        }
    }

    override suspend fun eliminar(usuarioId: String) = withContext(dispatcher) {
        db.avisoRemotoPendienteQueries.eliminar(usuarioId)
        Unit
    }
}
