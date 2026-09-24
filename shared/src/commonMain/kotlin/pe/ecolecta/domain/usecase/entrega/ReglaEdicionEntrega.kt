package pe.ecolecta.domain.usecase.entrega

import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.inicioSemanaProveedor
import pe.ecolecta.domain.repository.GestionPortalRepository
import pe.ecolecta.domain.usecase.admin.fechaLima

/**
 * Cuándo una entrega ya no admite corrección ni anulación:
 * - anulada: la anulación es terminal;
 * - en conflicto: primero se decide qué valor vale (Conflictos), si no la corrección se mezclaría con esa decisión;
 * - su semana tiene una liquidación aprobada o pagada: el pago se calculó con los litros actuales y quedaría
 *   desactualizado. Se bloquea en lugar de recalcular porque todavía no existe un ajuste compensatorio auditado.
 */
class ReglaEdicionEntrega(private val portal: GestionPortalRepository) {
    suspend fun bloqueo(entrega: Entrega): EntregaInvalidaException? {
        if (entrega.anulada) return EntregaInvalidaException.YaAnulada
        if (entrega.syncState == SyncState.CONFLICT) return EntregaInvalidaException.ConflictoPendiente
        val semana = inicioSemanaProveedor(fechaLima(entrega.registradoEn))
        val liquidadas = portal.todosPagos().first()
            .filter { it.desde == semana.toString() && it.estado.uppercase() != "ANULADA" }
        if (liquidadas.isEmpty()) return null
        val pagada = liquidadas.all { it.estado.uppercase() in setOf("PAGADA", "PAGADO") }
        val texto = "${semana.day.toString().padStart(2, '0')}/${(semana.month.ordinal + 1).toString().padStart(2, '0')}/${semana.year}"
        return EntregaInvalidaException.SemanaLiquidada(texto, pagada)
    }
}
