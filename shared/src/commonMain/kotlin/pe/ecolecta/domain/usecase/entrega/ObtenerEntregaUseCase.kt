package pe.ecolecta.domain.usecase.entrega

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.EntregaRepository

class ObtenerEntregaUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(id: String): Entrega? = entregaRepository.obtenerPorId(id)
}
