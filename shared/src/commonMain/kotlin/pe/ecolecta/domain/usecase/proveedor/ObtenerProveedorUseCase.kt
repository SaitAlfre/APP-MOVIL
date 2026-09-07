package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

class ObtenerProveedorUseCase(private val proveedorRepository: ProveedorRepository) {
    suspend operator fun invoke(id: String): Proveedor? = proveedorRepository.obtenerPorId(id)
}
