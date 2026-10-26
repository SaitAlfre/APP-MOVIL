package pe.ecolecta.domain.usecase.sync

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import pe.ecolecta.domain.repository.CambiosLocalesRepository
import pe.ecolecta.domain.repository.RechazoServidorException
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinSesionServidorException

/** [enviados] llegaron al panel; [conError] los rechazó por el dato; [sinConexion] = se dejó para el próximo ciclo. */
data class ResultadoCambios(val enviados: Int, val conError: Int, val sinConexion: Boolean)

/**
 * Celular -> panel web: envía los cambios hechos en la app (cuentas, zonas, vehículos, proveedores, jornadas,
 * comunicados, calidad, reclamos y liquidaciones) con el token de quien los hizo. Las entregas siguen su
 * propio camino ([SincronizarRegistrosAcopioUseCase]).
 *
 * - Un rechazo por el dato queda en error (se ve en Sincronización) hasta que se vuelva a cambiar la fila.
 * - Un rechazo "espera" (p. ej. calidad sin su entrega aún) o una cuenta sin sesión se reintenta después.
 * - Sin red se detiene y lo deja todo pendiente. Es `single`: el [Mutex] evita envíos simultáneos.
 */
class EnviarCambiosServidorUseCase(
    private val servidor: ServidorWebRepository,
    private val cambios: CambiosLocalesRepository,
) {
    private val mutex = Mutex()

    suspend operator fun invoke(usuarioId: String? = null): ResultadoCambios {
        if (!servidor.configurado) return ResultadoCambios(0, 0, false)
        return mutex.withLock { enviar(usuarioId) }
    }

    private suspend fun enviar(usuarioId: String?): ResultadoCambios {
        var enviados = 0
        var conError = 0
        for (cambio in cambios.pendientes()) {
            if (usuarioId != null && cambio.usuarioId != usuarioId) continue
            if (cambio.motivoNoEnviable != null) {
                cambios.fallar(cambio, cambio.motivoNoEnviable, definitivo = true)
                conError++
                continue
            }
            val cuerpo = cambio.cuerpo
            if (cuerpo == null) {
                cambios.completar(cambio, null) // nada que enviar (fila borrada o no la recibe el panel)
                continue
            }
            if (cambio.usuarioId.isBlank()) {
                cambios.fallar(cambio, "Falta la cuenta autora del cambio; no se ha enviado al panel.", definitivo = false)
                continue
            }
            val esCatalogo = cambio.entidad in setOf("zona", "vehiculo", "usuario", "proveedor")
            val resultado = try {
                if (esCatalogo) servidor.enviarCatalogo(cambio.usuarioId, cambio.entidad, cuerpo)
                else servidor.enviarCambio(cambio.usuarioId, cambio.entidadRemota ?: if (cambio.entidad == "solicitud") "reclamo" else cambio.entidad, cuerpo)
                    .map { pe.ecolecta.domain.repository.ConfirmacionCatalogo(it) }
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                Result.failure(e)
            }
            resultado.fold(
                onSuccess = { ack ->
                    if (esCatalogo) cambios.completarCatalogo(cambio, ack) else cambios.completar(cambio, ack.servidorId)
                    enviados++
                },
                onFailure = { error ->
                    when (error) {
                        is SinConexionRemotaException -> return ResultadoCambios(enviados, conError, sinConexion = true)
                        is SinSesionServidorException -> cambios.fallar(cambio, error.message.orEmpty(), definitivo = false)
                        is RechazoServidorException -> {
                            cambios.fallar(cambio, error.message.orEmpty(), definitivo = error.definitivo)
                            if (error.definitivo) conError++
                        }
                        else -> cambios.fallar(cambio, error.message ?: "No se pudo enviar al panel web.", definitivo = false)
                    }
                },
            )
        }
        return ResultadoCambios(enviados, conError, sinConexion = false)
    }
}
