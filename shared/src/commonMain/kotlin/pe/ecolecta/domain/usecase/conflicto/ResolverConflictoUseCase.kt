package pe.ecolecta.domain.usecase.conflicto

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.OrigenValorConflicto
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository

/** ADMIN decide explícitamente el valor correcto; nunca se aplica last-write-wins a los litros (§14). */
class ResolverConflictoUseCase(
    private val entregaRepository: EntregaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(
        entregaId: String,
        origen: OrigenValorConflicto,
        motivo: String,
        usuarioId: String,
    ): Result<Unit> {
        if (motivo.isBlank()) return Result.failure(EntregaInvalidaException.MotivoObligatorio)

        val entrega = entregaRepository.obtenerPorId(entregaId)
            ?: return Result.failure(IllegalStateException("Entrega no encontrada"))
        if (entrega.syncState != SyncState.CONFLICT) return Result.failure(EntregaInvalidaException.NoEsConflicto)

        val litrosFinal: Double
        val tachosFinal: Int
        val estadoFinal: SyncState
        when (origen) {
            OrigenValorConflicto.LOCAL -> {
                litrosFinal = entrega.litros
                tachosFinal = entrega.tachos
                // Vuelve a PENDING para que el próximo sync sobrescriba el valor del servidor con el local elegido.
                estadoFinal = SyncState.PENDING
            }
            OrigenValorConflicto.SERVIDOR -> {
                litrosFinal = entrega.litrosServidor ?: entrega.litros
                tachosFinal = (entrega.tachosServidor ?: entrega.tachos.toDouble()).toInt()
                estadoFinal = SyncState.SYNCED
            }
        }

        val ahora = reloj.ahora().toEpochMilliseconds()
        val auditoria = Auditoria(
            id = nuevoId(),
            entidad = "entrega",
            entidadId = entregaId,
            accion = AccionAuditoria.RESOLVER_CONFLICTO,
            valorAntes = "local=${entrega.litros};servidor=${entrega.litrosServidor}",
            valorDespues = "litros=$litrosFinal;tachos=$tachosFinal;origen=$origen",
            motivo = motivo,
            usuarioId = usuarioId,
            ocurridoEn = ahora,
            deviceId = deviceIdProvider.obtenerId(),
            syncState = SyncState.PENDING,
        )

        return runCatching {
            entregaRepository.resolverConflicto(entregaId, litrosFinal, tachosFinal, estadoFinal, ahora, auditoria)
        }
    }
}
