package pe.ecolecta.domain.usecase.traslado

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.repository.TrasladoRepository

class ListarTrasladosUseCase(private val trasladoRepository: TrasladoRepository) {
    operator fun invoke(soloPendientes: Boolean = false): Flow<List<TrasladoZona>> =
        if (soloPendientes) trasladoRepository.observarPendientes() else trasladoRepository.observarTodos()
}
