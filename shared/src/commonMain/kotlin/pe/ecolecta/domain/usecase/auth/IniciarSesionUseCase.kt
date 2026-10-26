package pe.ecolecta.domain.usecase.auth

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withTimeoutOrNull
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.DatosServidorLocalRepository
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
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
        val conocida = local.getOrNull()
        if (conocida != null && (!servidor.configurado || servidor.observarSesion(conocida.id).first())) {
            return Result.success(InicioSesion(conocida, enlazado = servidor.configurado))
        }
        val error = local.exceptionOrNull()
        if (conocida == null && (!servidor.configurado || (error !is PinInvalidoException.Incorrecto && error !is PinInvalidoException.Inactivo))) {
            return Result.failure(error!!)
        }
        // Un solo acceso obtiene también la sesión de sincronización. Sin red, una cuenta
        // previamente validada conserva su acceso local; el PIN no se guarda para reintentarlo.
        val remoto = withTimeoutOrNull(8_000) { servidor.autenticar(username.trim(), pin) }
        if (conocida != null && (remoto == null || remoto.exceptionOrNull() is SinConexionRemotaException)) {
            return Result.success(InicioSesion(conocida, enlazado = false))
        }
        val sesion = remoto?.getOrNull()
        val cuenta = sesion?.cuenta
        if (sesion == null || cuenta == null || cuenta.roles.isEmpty() || !cuenta.activo) {
            return Result.failure(remoto?.exceptionOrNull() ?: error ?: IllegalStateException("No se pudo confirmar tu acceso. Intenta con conexión."))
        }

        val par = pinHasher.crearHash(pin)
        val id = datosLocales.guardarCuenta(cuenta, par.hash, par.salt, reloj.ahora().toEpochMilliseconds())
        servidor.guardarSesion(id, sesion)
        val usuario = usuarios.obtenerPorId(id) ?: return Result.failure(IllegalStateException("No se pudo guardar tu cuenta en el teléfono."))
        return Result.success(InicioSesion(usuario, enlazado = true))
    }
}
