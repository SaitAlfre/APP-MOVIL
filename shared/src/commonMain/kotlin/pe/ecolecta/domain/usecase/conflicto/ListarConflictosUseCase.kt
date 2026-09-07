package pe.ecolecta.domain.usecase.conflicto

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.EntregaRepository

class ListarConflictosUseCase(private val entregaRepository: EntregaRepository) {
    operator fun invoke(): Flow<List<Entrega>> = entregaRepository.observarConflictos()
}
