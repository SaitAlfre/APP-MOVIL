package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.EntregaRepository

/** Nunca confía en el id de entrega por sí solo: si no pertenece a [proveedorId] no existe para este llamador (§11, §39). */
class ObtenerDetalleEntregaUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(entregaId: String, proveedorId: String): Entrega? {
        val entrega = entregaRepository.obtenerPorId(entregaId) ?: return null
        return entrega.takeIf { it.proveedorId == proveedorId }
    }
}
