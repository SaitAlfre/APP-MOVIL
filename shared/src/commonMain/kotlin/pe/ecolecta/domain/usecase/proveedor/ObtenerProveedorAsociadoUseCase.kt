package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.ProveedorSesionException
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.ProveedorRepository

/**
 * Resuelve el proveedor asociado a un usuario ya autenticado (§5, §39): nunca acepta un
 * proveedor_id arbitrario, siempre deriva el proveedor a partir de la identidad del usuario.
 */
class ObtenerProveedorAsociadoUseCase(private val proveedorRepository: ProveedorRepository) {
    suspend operator fun invoke(usuario: Usuario): Result<Proveedor> {
        if (Rol.PROVEEDOR !in usuario.roles) return Result.failure(ProveedorSesionException.SinRolProveedor)
        val proveedor = proveedorRepository.obtenerPorUsuarioId(usuario.id)
            ?: return Result.failure(ProveedorSesionException.SinProveedorAsociado)
        return Result.success(proveedor)
    }
}
