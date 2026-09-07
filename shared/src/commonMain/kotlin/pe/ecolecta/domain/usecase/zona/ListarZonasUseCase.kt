package pe.ecolecta.domain.usecase.zona

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.ZonaRepository

class ListarZonasUseCase(private val zonaRepository: ZonaRepository) {
    operator fun invoke(soloActivas: Boolean = false): Flow<List<Zona>> =
        if (soloActivas) zonaRepository.observarActivas() else zonaRepository.observarTodas()
}
