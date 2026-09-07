package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.UsuarioRepository

class AsignarRolUseCase(private val usuarioRepository: UsuarioRepository) {
    suspend operator fun invoke(usuarioId: String, rol: Rol): Result<Unit> = runCatching {
        usuarioRepository.asignarRol(usuarioId, rol)
    }
}
