package pe.ecolecta.presentation.design

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState

/**
 * Un único lugar donde el estado de sincronización se traduce a texto y color, para ACOPIADOR y
 * PROVEEDOR. Antes cada pantalla pintaba su propio chip y varias usaban [Colores.info] para todos
 * los estados, de modo que "sincronizado" y "pendiente" se veían igual; el diseño distingue verde
 * (sincronizado), ámbar (pendiente/conflicto) y rojo (error).
 */
@Composable
fun colorSync(estado: SyncState): Color = when (estado) {
    SyncState.SYNCED -> Colores.exito
    SyncState.PENDING -> Colores.advertencia
    SyncState.SYNCING -> Colores.info
    SyncState.ERROR -> Colores.peligro
    SyncState.CONFLICT -> Colores.advertencia
}

/** Etiqueta corta en mayúsculas (SYNCED, PENDING…) para las insignias de listas y resúmenes. */
fun etiquetaCortaSync(estado: SyncState): String = estado.name

/** Etiqueta larga y legible, para pantallas de detalle donde hay espacio para explicar el estado. */
fun etiquetaSync(estado: SyncState): String = when (estado) {
    SyncState.PENDING -> "Pendiente de sincronización"
    SyncState.SYNCING -> "Sincronizando…"
    SyncState.SYNCED -> "Sincronizado"
    SyncState.ERROR -> "Error de sincronización"
    SyncState.CONFLICT -> "Requiere revisión"
}

/**
 * Insignia de sincronización de una entrega. Una entrega anulada se muestra siempre como ANULADA
 * en rojo, sin importar si alcanzó a sincronizarse: para quien la lee, lo relevante es que ya no
 * cuenta.
 */
@Composable
fun ChipSync(entrega: Entrega, modifier: Modifier = Modifier, larga: Boolean = false) {
    if (entrega.anulada) {
        ChipEstado(if (larga) "Anulada" else "ANULADA", Colores.peligro, modifier, mostrarPunto = false)
    } else {
        ChipEstado(
            if (larga) etiquetaSync(entrega.syncState) else etiquetaCortaSync(entrega.syncState),
            colorSync(entrega.syncState),
            modifier,
            mostrarPunto = false,
        )
    }
}
