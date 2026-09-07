package pe.ecolecta.domain.model

import pe.ecolecta.domain.UsuarioInvalidoException

data class Usuario(
    val id: String,
    val username: String,
    val nombres: String,
    val dni: String,
    val pinHash: String,
    val pinSalt: String,
    val activo: Boolean,
    val roles: List<Rol>,
    val updatedAt: Long,
    val intentosFallidos: Int = 0,
    val bloqueadoHasta: Long? = null,
) {
    companion object {
        fun crear(
            id: String,
            username: String,
            nombres: String,
            dni: String,
            pinHash: String,
            pinSalt: String,
            activo: Boolean,
            roles: List<Rol>,
            updatedAt: Long,
            intentosFallidos: Int = 0,
            bloqueadoHasta: Long? = null,
        ): Result<Usuario> {
            if (username.isBlank()) return Result.failure(UsuarioInvalidoException.UsernameVacio)
            if (nombres.isBlank()) return Result.failure(UsuarioInvalidoException.NombresVacios)
            if (dni.isBlank()) return Result.failure(UsuarioInvalidoException.DniVacio)
            if (roles.isEmpty()) return Result.failure(UsuarioInvalidoException.SinRoles)
            return Result.success(
                Usuario(
                    id = id,
                    username = username.trim(),
                    nombres = nombres.trim(),
                    dni = dni.trim(),
                    pinHash = pinHash,
                    pinSalt = pinSalt,
                    activo = activo,
                    roles = roles,
                    updatedAt = updatedAt,
                    intentosFallidos = intentosFallidos,
                    bloqueadoHasta = bloqueadoHasta,
                ),
            )
        }
    }
}
