package pe.ecolecta.domain.usecase.seguimiento

import pe.ecolecta.domain.repository.UbicacionAcopiadorLocalRepository
import pe.ecolecta.domain.repository.UbicacionLocalAcopiador

class ObtenerUbicacionLocalUseCase(private val repository: UbicacionAcopiadorLocalRepository) {
    suspend operator fun invoke(usuarioId: String): UbicacionLocalAcopiador? = repository.obtener(usuarioId)
}
