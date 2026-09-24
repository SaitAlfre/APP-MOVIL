package pe.ecolecta.domain.usecase.auth

import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.DatosServidorLocalRepository
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.PinHasher

/** [enlazado] = el servidor ya validó la cuenta y su token quedó guardado (no hace falta volver a enlazar). */
data class InicioSesion(val usuario: Usuario, val enlazado: Boolean)

/**
 * Primero intenta sin red ([LoginOfflineUseCase]). Si el celular no conoce la cuenta, o el PIN no coincide
 * (p. ej. la cuenta se creó o se cambió en el panel web) y hay servidor, valida con el panel: si acepta,
 * guarda aquí la cuenta con ese PIN (así la próxima vez entra sin conexión) y su token.
 * Una cuenta bloqueada en el celular por intentos fallidos sigue bloqueada: no se usa el servidor para saltarlo.
 */
class IniciarSesionUseCase(
    private val loginOffline: LoginOfflineUseCase,
    private val servidor: ServidorWebRepository,
    private val datosLocales: DatosServidorLocalRepository,
    private val usuarios: UsuarioRepository,
    private val pinHasher: PinHasher,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(username: String, pin: String): Result<InicioSesion> {
        val local = loginOffline(username, pin)
        local.getOrNull()?.let { return Result.success(InicioSesion(it, enlazado = false)) }
        val error = local.exceptionOrNull()
        if (!servidor.configurado || (error !is PinInvalidoException.Incorrecto && error !is PinInvalidoException.Inactivo)) {
            return Result.failure(error!!)
        }
        val sesion = servidor.autenticar(username.trim(), pin).getOrNull()
        val cuenta = sesion?.cuenta
        if (sesion == null || cuenta == null || cuenta.roles.isEmpty() || !cuenta.activo) return Result.failure(error)

        val par = pinHasher.crearHash(pin)
        val id = datosLocales.guardarCuenta(cuenta, par.hash, par.salt, reloj.ahora().toEpochMilliseconds())
        servidor.guardarSesion(id, sesion)
        val usuario = usuarios.obtenerPorId(id) ?: return Result.failure(error)
        return Result.success(InicioSesion(usuario, enlazado = true))
    }
}
