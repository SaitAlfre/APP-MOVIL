package pe.ecolecta.domain.usecase.proveedor

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

class ListarProveedoresUseCase(private val proveedorRepository: ProveedorRepository) {
    operator fun invoke(zonaId: String? = null): Flow<List<Proveedor>> =
        if (zonaId == null) proveedorRepository.observarTodos() else proveedorRepository.observarPorZona(zonaId)
}
