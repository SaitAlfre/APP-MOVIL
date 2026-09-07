package pe.ecolecta.domain.usecase.traslado

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.TrasladoInvalidoException
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.TrasladoRepository

class RechazarTrasladoUseCase(
    private val trasladoRepository: TrasladoRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(trasladoId: String, rechazadoPor: String, motivo: String): Result<Unit> {
        val traslado = trasladoRepository.obtenerPorId(trasladoId)
            ?: return Result.failure(IllegalStateException("Traslado no encontrado"))
        if (traslado.estado != EstadoTraslado.PENDIENTE) return Result.failure(TrasladoInvalidoException.NoPendiente)

        val ahora = reloj.ahora().toEpochMilliseconds()
        val auditoria = Auditoria(
            id = nuevoId(),
            entidad = "traslado_zona",
            entidadId = trasladoId,
            accion = AccionAuditoria.RECHAZAR,
            valorAntes = null,
            valorDespues = null,
            motivo = motivo,
            usuarioId = rechazadoPor,
            ocurridoEn = ahora,
            deviceId = deviceIdProvider.obtenerId(),
            syncState = SyncState.PENDING,
        )

        return runCatching { trasladoRepository.rechazar(trasladoId, rechazadoPor, auditoria) }
    }
}
