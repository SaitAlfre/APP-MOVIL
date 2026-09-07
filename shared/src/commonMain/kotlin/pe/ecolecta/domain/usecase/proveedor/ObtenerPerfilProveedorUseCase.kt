package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

class ObtenerPerfilProveedorUseCase(private val proveedorRepository: ProveedorRepository) {
    suspend operator fun invoke(usuarioId: String): Proveedor? = proveedorRepository.obtenerPorUsuarioId(usuarioId)
}
