package pe.ecolecta.domain.usecase.usuario

import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.UsuarioInvalidoException
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.PinHasher

private val FORMATO_PIN = Regex("^\\d{4}$")

/**
 * Actualiza datos base + roles + (opcionalmente) el PIN de un usuario existente en una única
 * transacción (ver [UsuarioRepository.actualizarCompleto]): si cualquier paso falla, no queda ningún
 * cambio aplicado — a diferencia de encadenar [ActualizarUsuarioUseCase]/[AsignarRolUseCase]/
 * [QuitarRolUseCase]/[CambiarPinUsuarioUseCase] como llamadas independientes, donde un fallo a mitad
 * de camino (p. ej. al asignar el segundo de tres roles nuevos) deja al usuario a medio actualizar.
 */
class ActualizarUsuarioCompletoUseCase(
    private val usuarioRepository: UsuarioRepository,
    private val pinHasher: PinHasher,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(
        id: String,
        nombres: String,
        dni: String,
        activo: Boolean,
        rolesFinales: Set<Rol>,
        rolesOriginales: Set<Rol>,
        nuevoPin: String? = null,
        confirmacionPin: String? = null,
    ): Result<Unit> {
        if (nombres.isBlank()) return Result.failure(UsuarioInvalidoException.NombresVacios)
        if (dni.isBlank()) return Result.failure(UsuarioInvalidoException.DniVacio)
        if (usuarioRepository.existeDni(dni.trim(), id)) return Result.failure(UsuarioInvalidoException.DniDuplicado)
        if (rolesFinales.isEmpty()) return Result.failure(UsuarioInvalidoException.SinRoles)

        val parHash = if (nuevoPin != null) {
            if (!FORMATO_PIN.matches(nuevoPin)) return Result.failure(PinInvalidoException.FormatoInvalido)
            if (nuevoPin != confirmacionPin) return Result.failure(PinInvalidoException.NoCoincideConfirmacion)
            pinHasher.crearHash(nuevoPin)
        } else {
            null
        }

        return runCatching {
            usuarioRepository.actualizarCompleto(
                id = id,
                nombres = nombres.trim(),
                dni = dni.trim(),
                activo = activo,
                rolesAgregados = (rolesFinales - rolesOriginales).toList(),
                rolesQuitados = (rolesOriginales - rolesFinales).toList(),
                nuevoPin = parHash,
                updatedAt = reloj.ahora().toEpochMilliseconds(),
            )
        }
    }
}
