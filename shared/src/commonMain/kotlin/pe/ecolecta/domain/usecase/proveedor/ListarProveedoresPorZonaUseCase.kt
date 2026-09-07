package pe.ecolecta.domain.usecase.proveedor

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

/** Solo proveedores ACTIVOS de la zona de la jornada; retirados/suspendidos no aparecen para registrar entregas (§22). */
class ListarProveedoresPorZonaUseCase(private val proveedorRepository: ProveedorRepository) {
    operator fun invoke(zonaId: String): Flow<List<Proveedor>> = proveedorRepository.observarActivosPorZona(zonaId)
}
