package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.UsuarioInvalidoException
import pe.ecolecta.domain.repository.UsuarioRepository

class ActualizarUsuarioUseCase(
    private val usuarioRepository: UsuarioRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(id: String, nombres: String, dni: String, activo: Boolean): Result<Unit> {
        if (nombres.isBlank()) return Result.failure(UsuarioInvalidoException.NombresVacios)
        if (dni.isBlank()) return Result.failure(UsuarioInvalidoException.DniVacio)
        if (usuarioRepository.existeDni(dni.trim(), id)) return Result.failure(UsuarioInvalidoException.DniDuplicado)

        return runCatching {
            usuarioRepository.actualizar(id, nombres.trim(), dni.trim(), activo, reloj.ahora().toEpochMilliseconds())
        }
    }
}
