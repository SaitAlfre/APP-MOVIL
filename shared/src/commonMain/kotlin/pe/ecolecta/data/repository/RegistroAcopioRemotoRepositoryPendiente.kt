package pe.ecolecta.data.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import pe.ecolecta.domain.acopio.RegistroAcopioCompartido
import pe.ecolecta.domain.repository.EventoRegistrosRemotos
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository

/**
 * Sin backend remoto en esta compilación (iOS, o Android con `-PlocalPreview=true`): nunca finge un
 * envío. Los registros se quedan "guardados en este celular" y el proveedor ve ese estado real.
 */
class RegistroAcopioRemotoRepositoryPendiente : RegistroAcopioRemotoRepository {
    override val configurado: Boolean = false

    override suspend fun publicar(registro: RegistroAcopioCompartido): Result<Unit> =
        Result.failure(UnsupportedOperationException("Sincronización remota no disponible en esta versión."))

    override fun observarDeProveedor(proveedorCodigo: String): Flow<EventoRegistrosRemotos> =
        flowOf(EventoRegistrosRemotos.NoDisponible("La sincronización entre celulares no está disponible en esta versión."))
}
