package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.EntregaRepository

/** [proveedorId] siempre proviene de la sesión activa, nunca de un parámetro elegible por la UI (§11, §22). */
class FiltrarMisEntregasUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(
        proveedorId: String,
        jornadaId: String? = null,
        syncState: SyncState? = null,
        desde: Long? = null,
        hasta: Long? = null,
    ): List<Entrega> = entregaRepository.filtrar(proveedorId = proveedorId, jornadaId = jornadaId, syncState = syncState)
        .filter { (desde == null || it.registradoEn >= desde) && (hasta == null || it.registradoEn < hasta) }
}
