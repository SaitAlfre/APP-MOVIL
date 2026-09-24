package pe.ecolecta.presentation.acopiador

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.colorSync

/**
 * Texto orientado al acopiador: explica la acción o el resultado en vez de
 * exponer el nombre técnico del estado de sincronización.
 */
internal fun etiquetaSyncAcopiador(estado: SyncState): String = when (estado) {
    SyncState.PENDING -> "Pendiente de envío"
    SyncState.SYNCING -> "Enviando…"
    SyncState.SYNCED -> "Sincronizada"
    SyncState.ERROR -> "Error al enviar"
    SyncState.CONFLICT -> "Requiere revisión"
}

/** Insignia exclusiva de Acopiador para las entregas de la jornada. */
@Composable
internal fun ChipSyncAcopiador(entrega: Entrega, modifier: Modifier = Modifier) {
    val texto = if (entrega.anulada) "Anulada" else etiquetaSyncAcopiador(entrega.syncState)
    val color = if (entrega.anulada) Colores.peligro else colorSync(entrega.syncState)
    ChipEstado(texto, color, modifier, mostrarPunto = false)
}
