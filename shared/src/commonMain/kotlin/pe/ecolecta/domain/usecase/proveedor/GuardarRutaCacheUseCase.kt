package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.RutaProveedorCacheRepository

class GuardarRutaCacheUseCase(private val repository: RutaProveedorCacheRepository) {
    suspend operator fun invoke(usuarioId: String, ubicacion: UbicacionAcopiador) = repository.guardar(usuarioId, ubicacion)
}
