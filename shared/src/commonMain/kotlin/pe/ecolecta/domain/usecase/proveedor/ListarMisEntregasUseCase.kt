package pe.ecolecta.domain.usecase.proveedor

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.EntregaRepository

class ListarMisEntregasUseCase(private val entregaRepository: EntregaRepository) {
    operator fun invoke(proveedorId: String): Flow<List<Entrega>> = entregaRepository.observarPorProveedor(proveedorId)
}
