package pe.ecolecta.domain.usecase.sync

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import pe.ecolecta.domain.repository.DatosServidorLocalRepository
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SesionRepository

/**
 * Panel web -> celular: descarga con el token de la cuenta en sesión lo que el panel publica para ella
 * (cuentas, zonas, vehículos, proveedores, jornadas, entregas, calidad y comunicados) y lo guarda aquí.
 * Así, lo que se cambia en la web aparece en la app en el siguiente ciclo (App.kt) o al iniciar sesión.
 *
 * Corre después de enviar lo pendiente del celular, y nunca pisa lo que aún no se envió. Sin servidor, sin
 * sesión o sin red no hace nada: la app sigue con su copia local. Es `single`: el [Mutex] evita dos
 * descargas a la vez.
 */
class SincronizarDatosServidorUseCase(
    private val servidor: ServidorWebRepository,
    private val local: DatosServidorLocalRepository,
    private val sesiones: SesionRepository,
) {
    private val mutex = Mutex()

    /** true si se aplicó una copia nueva del panel. */
    suspend operator fun invoke(usuarioId: String? = null): Boolean {
        if (!servidor.configurado) return false
        val id = usuarioId ?: sesiones.observar().first()?.usuario?.id ?: return false
        return mutex.withLock {
            val datos = servidor.descargarDatos(id).getOrElse { return@withLock false }
            try {
                local.aplicar(datos)
                true
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                false
            }
        }
    }
}
