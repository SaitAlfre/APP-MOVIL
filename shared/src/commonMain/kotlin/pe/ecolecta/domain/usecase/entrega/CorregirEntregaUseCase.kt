package pe.ecolecta.domain.usecase.entrega

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository

/**
 * UPDATE entrega + INSERT auditoria en una sola transacción; el motivo nunca es opcional (§12).
 * La entrega corregida vuelve a quedar pendiente de envío: el servidor todavía tiene el valor anterior.
 */
class CorregirEntregaUseCase(
    private val entregaRepository: EntregaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
    private val regla: ReglaEdicionEntrega,
) {
    suspend operator fun invoke(
        entregaId: String,
        litros: Double,
        tachos: Int,
        observaciones: String?,
        motivo: String,
        usuarioId: String,
    ): Result<Unit> {
        if (motivo.isBlank()) return Result.failure(EntregaInvalidaException.MotivoObligatorio)
        if (!litros.isFinite() || litros <= 0.0) return Result.failure(EntregaInvalidaException.LitrosNoPositivos)
        if (tachos <= 0) return Result.failure(EntregaInvalidaException.TachosInvalidos)

        val entrega = entregaRepository.obtenerPorId(entregaId)
            ?: return Result.failure(IllegalStateException("Entrega no encontrada"))
        regla.bloqueo(entrega)?.let { return Result.failure(it) }
        if (entrega.litros == litros && entrega.tachos == tachos) return Result.failure(EntregaInvalidaException.SinCambios)

        val ahora = reloj.ahora().toEpochMilliseconds()
        val auditoria = Auditoria(
            id = nuevoId(),
            entidad = "entrega",
            entidadId = entregaId,
            accion = AccionAuditoria.CORREGIR,
            valorAntes = "litros=${entrega.litros};tachos=${entrega.tachos}",
            valorDespues = "litros=$litros;tachos=$tachos",
            motivo = motivo.trim(),
            usuarioId = usuarioId,
            ocurridoEn = ahora,
            deviceId = deviceIdProvider.obtenerId(),
            syncState = SyncState.PENDING,
        )

        return runCatching {
            entregaRepository.corregir(entregaId, litros, tachos, observaciones, ahora, auditoria)
        }
    }
}
