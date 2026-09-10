package pe.ecolecta.domain.fake

import pe.ecolecta.domain.model.AvisoRemotoPendiente
import pe.ecolecta.domain.repository.AvisoRemotoPendienteRepository

class FakeAvisoRemotoPendienteRepository : AvisoRemotoPendienteRepository {
    private val avisos = mutableMapOf<String, AvisoRemotoPendiente>()

    override suspend fun guardar(aviso: AvisoRemotoPendiente) {
        avisos[aviso.usuarioId] = aviso
    }

    override suspend fun obtener(usuarioId: String): AvisoRemotoPendiente? = avisos[usuarioId]

    override suspend fun eliminar(usuarioId: String) {
        avisos.remove(usuarioId)
    }
}
