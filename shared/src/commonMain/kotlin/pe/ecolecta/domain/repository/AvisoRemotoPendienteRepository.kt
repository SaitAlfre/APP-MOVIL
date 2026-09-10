package pe.ecolecta.domain.repository

import pe.ecolecta.domain.model.AvisoRemotoPendiente

/** Persistencia local del aviso remoto pendiente de reintentar (ver [AvisoRemotoPendiente]). */
interface AvisoRemotoPendienteRepository {
    suspend fun guardar(aviso: AvisoRemotoPendiente)
    suspend fun obtener(usuarioId: String): AvisoRemotoPendiente?
    suspend fun eliminar(usuarioId: String)
}
