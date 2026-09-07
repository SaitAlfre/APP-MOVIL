package pe.ecolecta.domain.usecase.auth

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.PinHasher

/**
 * Login 100% local: valida usuario+PIN contra el hash guardado en SQLDelight, sin red (§18).
 * Bloquea la cuenta 5 minutos tras 5 intentos fallidos; el bloqueo persiste en la base local.
 */
class LoginOfflineUseCase(
    private val usuarioRepository: UsuarioRepository,
    private val pinHasher: PinHasher,
    private val auditoriaRepository: AuditoriaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    companion object {
        const val MAX_INTENTOS = 5
        const val BLOQUEO_MINUTOS = 5
    }

    suspend operator fun invoke(username: String, pin: String): Result<Usuario> {
        val usuario = usuarioRepository.obtenerPorUsername(username.trim())
            ?: return Result.failure(PinInvalidoException.Incorrecto)

        if (!usuario.activo) return Result.failure(PinInvalidoException.Inactivo)

        val ahoraMs = reloj.ahora().toEpochMilliseconds()
        val bloqueadoHasta = usuario.bloqueadoHasta
        if (bloqueadoHasta != null && ahoraMs < bloqueadoHasta) {
            return Result.failure(PinInvalidoException.Bloqueado(bloqueadoHasta))
        }

        val pinValido = pinHasher.verificar(pin, usuario.pinSalt, usuario.pinHash)
        if (!pinValido) {
            usuarioRepository.registrarIntentoFallido(usuario.id, ahoraMs)
            if (usuario.intentosFallidos + 1 >= MAX_INTENTOS) {
                usuarioRepository.bloquear(usuario.id, ahoraMs + BLOQUEO_MINUTOS * 60_000L, ahoraMs)
            }
            return Result.failure(PinInvalidoException.Incorrecto)
        }

        usuarioRepository.resetIntentos(usuario.id, ahoraMs)

        auditoriaRepository.insertar(
            Auditoria(
                id = nuevoId(),
                entidad = "usuario",
                entidadId = usuario.id,
                accion = AccionAuditoria.LOGIN,
                valorAntes = null,
                valorDespues = null,
                motivo = null,
                usuarioId = usuario.id,
                ocurridoEn = ahoraMs,
                deviceId = deviceIdProvider.obtenerId(),
                syncState = SyncState.PENDING,
            ),
        )

        return Result.success(usuario.copy(intentosFallidos = 0, bloqueadoHasta = null))
    }
}
