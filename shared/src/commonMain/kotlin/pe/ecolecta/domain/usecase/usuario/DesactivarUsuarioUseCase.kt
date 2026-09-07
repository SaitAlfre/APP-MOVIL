package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.repository.UsuarioRepository

class DesactivarUsuarioUseCase(
    private val usuarioRepository: UsuarioRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(id: String): Result<Unit> = runCatching {
        usuarioRepository.desactivar(id, reloj.ahora().toEpochMilliseconds())
    }
}
