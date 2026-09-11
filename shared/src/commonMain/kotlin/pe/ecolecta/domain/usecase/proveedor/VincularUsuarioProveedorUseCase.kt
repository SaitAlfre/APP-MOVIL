package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.ProveedorInvalidoException
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.UsuarioRepository

/**
 * Vincula una cuenta de Usuario (rol PROVEEDOR) a un registro Proveedor, para que
 * [pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorAsociadoUseCase] pueda resolverlo al iniciar
 * sesión. Sin este vínculo, un proveedor creado por el ADMIN nunca puede ver su perfil ni sus entregas.
 */
class VincularUsuarioProveedorUseCase(
    private val proveedorRepository: ProveedorRepository,
    private val usuarioRepository: UsuarioRepository,
) {
    suspend operator fun invoke(proveedorId: String, usuarioId: String): Result<Unit> = runCatching {
        val usuario = usuarioRepository.obtenerPorId(usuarioId)
            ?: throw NoSuchElementException("El usuario no existe.")
        if (Rol.PROVEEDOR !in usuario.roles) {
            throw ProveedorInvalidoException.UsuarioSinRolProveedor
        }
        proveedorRepository.obtenerPorId(proveedorId)
            ?: throw NoSuchElementException("El proveedor no existe.")

        val yaVinculadoA = proveedorRepository.obtenerPorUsuarioId(usuarioId)
        if (yaVinculadoA != null && yaVinculadoA.id != proveedorId) {
            throw ProveedorInvalidoException.UsuarioYaVinculado
        }

        proveedorRepository.vincularUsuario(proveedorId, usuarioId)
    }
}
