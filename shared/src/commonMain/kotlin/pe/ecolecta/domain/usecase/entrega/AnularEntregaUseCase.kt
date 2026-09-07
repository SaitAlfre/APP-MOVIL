package pe.ecolecta.domain.usecase.entrega

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository

/** anulada = true, nunca DELETE; UPDATE + INSERT auditoria en una sola transacción (§13). */
class AnularEntregaUseCase(
    private val entregaRepository: EntregaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(entregaId: String, motivo: String, usuarioId: String): Result<Unit> {
        if (motivo.isBlank()) return Result.failure(EntregaInvalidaException.MotivoObligatorio)

        entregaRepository.obtenerPorId(entregaId) ?: return Result.failure(IllegalStateException("Entrega no encontrada"))

        val ahora = reloj.ahora().toEpochMilliseconds()
        val auditoria = Auditoria(
            id = nuevoId(),
            entidad = "entrega",
            entidadId = entregaId,
            accion = AccionAuditoria.ANULAR,
            valorAntes = null,
            valorDespues = null,
            motivo = motivo,
            usuarioId = usuarioId,
            ocurridoEn = ahora,
            deviceId = deviceIdProvider.obtenerId(),
            syncState = SyncState.PENDING,
        )

        return runCatching { entregaRepository.anular(entregaId, ahora, auditoria) }
    }
}
