package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.PinHasher

private val FORMATO_PIN = Regex("^\\d{4}$")

/** Cuando ADMIN cambia el PIN de un usuario se genera un salt y hash nuevos (§4); nunca se reutiliza el anterior. */
class CambiarPinUsuarioUseCase(
    private val usuarioRepository: UsuarioRepository,
    private val pinHasher: PinHasher,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(id: String, nuevoPin: String, confirmacionPin: String): Result<Unit> {
        if (!FORMATO_PIN.matches(nuevoPin)) return Result.failure(PinInvalidoException.FormatoInvalido)
        if (nuevoPin != confirmacionPin) return Result.failure(PinInvalidoException.NoCoincideConfirmacion)

        val parHash = pinHasher.crearHash(nuevoPin)
        return runCatching {
            usuarioRepository.actualizarPin(id, parHash.hash, parHash.salt, reloj.ahora().toEpochMilliseconds())
        }
    }
}
