package pe.ecolecta.domain

sealed class UsuarioInvalidoException(mensaje: String) : Exception(mensaje) {
    data object UsernameVacio : UsuarioInvalidoException("El nombre de usuario es obligatorio.")
    data object NombresVacios : UsuarioInvalidoException("Los nombres son obligatorios.")
    data object DniVacio : UsuarioInvalidoException("El DNI es obligatorio.")
    data object SinRoles : UsuarioInvalidoException("El usuario debe tener al menos un rol.")
    data object UsernameDuplicado : UsuarioInvalidoException("Ya existe un usuario con ese username.")
    data object DniDuplicado : UsuarioInvalidoException("Ya existe un usuario con ese DNI.")
}

sealed class PinInvalidoException(mensaje: String) : Exception(mensaje) {
    data object FormatoInvalido : PinInvalidoException("El PIN debe tener exactamente 4 dígitos.")
    data object NoCoincideConfirmacion : PinInvalidoException("La confirmación del PIN no coincide.")
    data object Incorrecto : PinInvalidoException("Usuario o PIN incorrecto.")
    data class Bloqueado(val bloqueadoHastaEpochMs: Long) : PinInvalidoException("Cuenta bloqueada temporalmente por intentos fallidos.")
    data object Inactivo : PinInvalidoException("El usuario está inactivo.")
    data object SinPerfilMovil : PinInvalidoException("Esta cuenta no tiene un perfil móvil compatible. Sus roles se conservan; consulta al administrador.")
    data object RequiereConexion : PinInvalidoException("El servidor retiró el acceso anterior. Inicia sesión con conexión para validar tu cuenta; tus datos pendientes se conservan.")
}
