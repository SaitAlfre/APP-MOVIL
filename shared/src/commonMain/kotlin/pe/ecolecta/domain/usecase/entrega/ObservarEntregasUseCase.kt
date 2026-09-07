package pe.ecolecta.domain.usecase.entrega

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.EntregaRepository

/** Igual que [ListarEntregasUseCase], pero reactivo: usado por ADMIN para ver en vivo lo que ACOPIADOR registra o corrige. */
class ObservarEntregasUseCase(private val entregaRepository: EntregaRepository) {
    operator fun invoke(
        jornadaId: String? = null,
        proveedorId: String? = null,
        usuarioId: String? = null,
        zonaId: String? = null,
        vehiculoId: String? = null,
        syncState: SyncState? = null,
        loteId: String? = null,
    ): Flow<List<Entrega>> = entregaRepository.observarConFiltros(jornadaId, proveedorId, usuarioId, zonaId, vehiculoId, syncState, loteId)
}
