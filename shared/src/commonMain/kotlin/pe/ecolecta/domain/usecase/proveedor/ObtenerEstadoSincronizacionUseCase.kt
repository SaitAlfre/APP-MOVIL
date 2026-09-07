package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.ResumenSyncProveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.EntregaRepository

class ObtenerEstadoSincronizacionUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(proveedorId: String): ResumenSyncProveedor {
        val entregas = entregaRepository.filtrar(proveedorId = proveedorId)
        return ResumenSyncProveedor(
            pendientes = entregas.count { it.syncState == SyncState.PENDING },
            sincronizadas = entregas.count { it.syncState == SyncState.SYNCED },
            errores = entregas.count { it.syncState == SyncState.ERROR },
            conflictos = entregas.count { it.syncState == SyncState.CONFLICT },
        )
    }
}
