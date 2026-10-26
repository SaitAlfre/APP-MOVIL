package pe.ecolecta.domain.usecase.auth

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeoutOrNull
import pe.ecolecta.domain.PinInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.DatosServidorLocalRepository
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.security.PinHasher

/** [enlazado] = el servidor ya validó la cuenta y su token quedó guardado (no hace falta volver a enlazar). */
data class InicioSesion(val usuario: Usuario, val enlazado: Boolean)

class IniciarSesionUseCase(
    private val usuarios: UsuarioRepository,
    private val loginOffline: LoginOfflineUseCase,
    private val pinHasher: PinHasher,
    private val datosLocales: DatosServidorLocalRepository,
    private val servidor: ServidorWebRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(username: String, pin: String): Result<InicioSesion> {
        val local = loginOffline(username, pin, registrarAuditoria = false)
        val error = local.exceptionOrNull()
        if (!servidor.configurado) return inicioLocal(local)
        if (error != null && error !is PinInvalidoException.Incorrecto && error !is PinInvalidoException.Inactivo &&
            error !is PinInvalidoException.SinPerfilMovil && error !is PinInvalidoException.RequiereConexion
        ) {
            return Result.failure(error)
        }
        val respuesta = withTimeoutOrNull(8_000) { servidor.autenticar(username.trim(), pin) }
        val sesion = (respuesta ?: Result.failure(SinConexionRemotaException("Tiempo de espera agotado"))).getOrElse { remoto ->
            if (remoto is SinConexionRemotaException) return inicioLocal(local)
            if (remoto is RechazoServidorException && remoto.codigo in setOf("credenciales", "cuenta_bloqueada", "perfil_no_disponible")) {
                usuarios.obtenerPorUsername(username.trim())?.let { datosLocales.requerirVerificacion(it.id) }
            }
            return Result.failure(remoto)
        }
        val cuenta = sesion.cuenta
            ?: return Result.failure(IllegalStateException("El servidor no devolvió el perfil de la cuenta."))
        if (cuenta.roles.none { Rol.desde(it.uppercase()) != null } || !cuenta.activo || cuenta.bloqueado) {
            usuarios.obtenerPorUsername(username.trim())?.let { datosLocales.requerirVerificacion(it.id) }
            return Result.failure(if (cuenta.activo && !cuenta.bloqueado) PinInvalidoException.SinPerfilMovil else PinInvalidoException.Inactivo)
        }

        val par = withContext(Dispatchers.Default) { pinHasher.crearHash(pin) }
        val id = try {
            datosLocales.guardarCuenta(cuenta, par.hash, par.salt, reloj.ahora().toEpochMilliseconds())
                .also { servidor.guardarSesion(it, sesion) }
        } catch (cancelacion: CancellationException) {
            throw cancelacion
        } catch (error: Exception) {
            return Result.failure(error)
        }
        val usuario = usuarios.obtenerPorId(id) ?: return Result.failure(IllegalStateException("No se pudo guardar la cuenta validada."))
        loginOffline.registrarAcceso(usuario)
        return Result.success(InicioSesion(usuario, enlazado = true))
    }

    private suspend fun inicioLocal(resultado: Result<Usuario>): Result<InicioSesion> {
        val usuario = resultado.getOrElse { return Result.failure(it) }
        loginOffline.registrarAcceso(usuario)
        return Result.success(InicioSesion(usuario, enlazado = false))
    }
}
