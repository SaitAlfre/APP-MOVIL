package pe.ecolecta.domain.usecase.sync

import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
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
    /** Qué hacer tras enlazar (en la app: enviar lo pendiente con [SincronizarRegistrosAcopioUseCase]). */
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
                    val motivo = if (error is SinConexionRemotaException) {
                        "No se pudo contactar al servidor al iniciar sesión. Vuelve a iniciar sesión con conexión para enlazar tu cuenta."
                    } else {
                        "El servidor no aceptó tu cuenta: ${error.message}"
                    }
                    _errores.update { it + (usuarioId to motivo) }
                },
            )
        }
    }
}
