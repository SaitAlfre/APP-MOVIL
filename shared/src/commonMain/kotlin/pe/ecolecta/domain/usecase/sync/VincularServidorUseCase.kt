package pe.ecolecta.domain.usecase.sync

import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException

/**
 * Tras un login local correcto, enlaza la cuenta con el panel web usando el MISMO usuario y PIN: el PIN
 * solo está disponible en ese momento y nunca se guarda (se guarda el token que emite el servidor).
 *
 * Corre en [scope] (de la app, no de la pantalla): el login offline no espera a la red y el enlace no se
 * cancela al navegar. Si lo logra, envía enseguida lo pendiente. [errores] conserva el último motivo por
 * usuario (p. ej. PIN distinto en el servidor) para mostrarlo en Sincronización en vez de ocultarlo.
 */
class VincularServidorUseCase(
    private val servidor: ServidorWebRepository,
    /**
     * Qué hacer tras enlazar (en la app: enviar lo pendiente con [SincronizarRegistrosAcopioUseCase] y traer
     * los datos del panel con [SincronizarDatosServidorUseCase]).
     */
    private val alEnlazar: suspend () -> Unit,
    private val scope: CoroutineScope,
) {
    private val _errores = MutableStateFlow<Map<String, String>>(emptyMap())
    val errores: StateFlow<Map<String, String>> = _errores.asStateFlow()

    operator fun invoke(usuarioId: String, username: String, pin: String) {
        if (!servidor.configurado) return
        scope.launch {
            servidor.vincular(usuarioId, username, pin).fold(
                onSuccess = {
                    _errores.update { it - usuarioId }
                    runCatching { alEnlazar() }
                },
                onFailure = { error ->
                    val motivo = when {
                        error is SinConexionRemotaException ->
                            "Tu cuenta no quedó enlazada con el panel web: al iniciar sesión el servidor no respondió" +
                                (servidor.direccion?.let { " en $it" } ?: "") + ". Tus entregas siguen guardadas aquí. " +
                                "Cuando el panel esté disponible, cierra sesión y vuelve a entrar con tu usuario y PIN."
                        error is RechazoServidorException && error.codigo == "credenciales" ->
                            "El panel web no reconoce tu usuario o PIN (deben ser los mismos que en el panel). " +
                                "Pide al administrador que revise tu cuenta allí y vuelve a iniciar sesión. Tus entregas siguen guardadas aquí."
                        else -> "El panel web no aceptó tu cuenta: ${error.message}"
                    }
                    _errores.update { it + (usuarioId to motivo) }
                },
            )
        }
    }

    /** La cuenta ya quedó enlazada al iniciar sesión (validada por el panel): solo corre [alEnlazar]. */
    fun yaEnlazado() {
        if (!servidor.configurado) return
        scope.launch { runCatching { alEnlazar() } }
    }

    /** true si [usuarioId] tiene un token vigente del panel en este celular (no basta haber entrado offline). */
    suspend fun enlazado(usuarioId: String): Boolean = servidor.configurado && servidor.observarSesion(usuarioId).first()

    fun observarEnlace(usuarioId: String) = servidor.observarSesion(usuarioId)
}
