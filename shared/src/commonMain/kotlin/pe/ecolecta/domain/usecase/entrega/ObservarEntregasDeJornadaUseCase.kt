package pe.ecolecta.domain.usecase.entrega

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.EntregaRepository

class ObservarEntregasDeJornadaUseCase(private val entregaRepository: EntregaRepository) {
    operator fun invoke(jornadaId: String): Flow<List<Entrega>> = entregaRepository.observarPorJornada(jornadaId)
}
