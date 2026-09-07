package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.UsuarioInvalidoException
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.PinHasher

private val FORMATO_PIN = Regex("^\\d{4}$")

class CrearUsuarioUseCase(
    private val usuarioRepository: UsuarioRepository,
    private val pinHasher: PinHasher,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(
        username: String,
        nombres: String,
        dni: String,
        pin: String,
        confirmacionPin: String,
        roles: List<Rol>,
        activo: Boolean = true,
    ): Result<Usuario> {
        if (!FORMATO_PIN.matches(pin)) return Result.failure(PinInvalidoException.FormatoInvalido)
        if (pin != confirmacionPin) return Result.failure(PinInvalidoException.NoCoincideConfirmacion)
        if (usuarioRepository.existeUsername(username.trim(), "")) {
            return Result.failure(UsuarioInvalidoException.UsernameDuplicado)
        }
        if (usuarioRepository.existeDni(dni.trim(), "")) {
            return Result.failure(UsuarioInvalidoException.DniDuplicado)
        }

        val parHash = pinHasher.crearHash(pin)
        val usuario = Usuario.crear(
            id = nuevoId(),
            username = username,
            nombres = nombres,
            dni = dni,
            pinHash = parHash.hash,
            pinSalt = parHash.salt,
            activo = activo,
            roles = roles,
            updatedAt = reloj.ahora().toEpochMilliseconds(),
        ).getOrElse { return Result.failure(it) }

        return runCatching {
            usuarioRepository.insertar(usuario)
            usuario
        }
    }
}
