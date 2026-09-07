package pe.ecolecta.domain.usecase.entrega

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.EntregaRepository

class ListarEntregasUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(
        jornadaId: String? = null,
        proveedorId: String? = null,
        usuarioId: String? = null,
        zonaId: String? = null,
        vehiculoId: String? = null,
        syncState: SyncState? = null,
        loteId: String? = null,
    ): List<Entrega> = entregaRepository.filtrar(jornadaId, proveedorId, usuarioId, zonaId, vehiculoId, syncState, loteId)
}
