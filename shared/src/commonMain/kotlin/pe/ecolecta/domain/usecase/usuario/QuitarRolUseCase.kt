package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.UsuarioInvalidoException
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.UsuarioRepository

class QuitarRolUseCase(private val usuarioRepository: UsuarioRepository) {
    suspend operator fun invoke(usuarioId: String, rol: Rol): Result<Unit> {
        val usuario = usuarioRepository.obtenerPorId(usuarioId)
            ?: return Result.failure(IllegalStateException("Usuario no encontrado"))
        if (usuario.roles.size <= 1) return Result.failure(UsuarioInvalidoException.SinRoles)

        return runCatching { usuarioRepository.quitarRol(usuarioId, rol) }
    }
}
