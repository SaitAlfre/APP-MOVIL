package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.RutaProveedorCacheRepository

class ObtenerRutaCacheUseCase(private val repository: RutaProveedorCacheRepository) {
    suspend operator fun invoke(usuarioId: String): UbicacionAcopiador? = repository.obtener(usuarioId)
}
