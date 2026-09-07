package pe.ecolecta.domain.usecase.sync

import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.EntregaRepository

data class ResumenColaSync(val pendientes: Int, val sincronizados: Int, val errores: Int, val conflictos: Int)

class ObtenerColaSyncUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(usuarioId: String): ResumenColaSync = ResumenColaSync(
        pendientes = entregaRepository.filtrar(usuarioId = usuarioId, syncState = SyncState.PENDING).size,
        sincronizados = entregaRepository.filtrar(usuarioId = usuarioId, syncState = SyncState.SYNCED).size,
        errores = entregaRepository.filtrar(usuarioId = usuarioId, syncState = SyncState.ERROR).size,
        conflictos = entregaRepository.filtrar(usuarioId = usuarioId, syncState = SyncState.CONFLICT).size,
    )
}
