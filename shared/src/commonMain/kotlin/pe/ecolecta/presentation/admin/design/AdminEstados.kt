package pe.ecolecta.presentation.admin.design

import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.domain.model.SyncState

/**
 * Textos del Administrador para los estados internos. Los nombres en inglés (PENDING, SYNCED…) son
 * del código y la base; ADMIN siempre ve estas etiquetas, iguales en la lista de entregas, su detalle
 * y el detalle de jornada.
 */
object TextosEstado {
    /** Etiqueta corta para insignias de listas. */
    fun corta(estado: SyncState): String = when (estado) {
        SyncState.PENDING -> "Pendiente"
        SyncState.SYNCING -> "Sincronizando"
        SyncState.SYNCED -> "Sincronizada"
        SyncState.ERROR -> "Error de sync"
        SyncState.CONFLICT -> "Conflicto"
    }

    /** Etiqueta completa para el detalle y la leyenda. */
    fun larga(estado: SyncState): String = when (estado) {
        SyncState.PENDING -> "Pendiente de sincronización"
        SyncState.SYNCING -> "Sincronizando"
        SyncState.SYNCED -> "Sincronizada"
        SyncState.ERROR -> "Error de sincronización"
        SyncState.CONFLICT -> "Conflicto por resolver"
    }

    fun ayuda(estado: SyncState): String = when (estado) {
        SyncState.PENDING -> "Guardada en este teléfono; todavía no se ha enviado al servidor."
        SyncState.SYNCING -> "Se está enviando en este momento."
        SyncState.SYNCED -> "El servidor la recibió y aceptó."
        SyncState.ERROR -> "Se intentó enviar y falló. El dato sigue guardado en el teléfono."
        SyncState.CONFLICT -> "El valor del teléfono y el del servidor no coinciden: ADMIN decide cuál conservar en Conflictos."
    }

    const val ANULADA = "Anulada"
    const val AYUDA_ANULADA = "Ya no cuenta en litros, reportes ni liquidaciones. La anulación es definitiva."

    fun traslado(estado: EstadoTraslado): String = when (estado) {
        EstadoTraslado.PENDIENTE -> "Por autorizar"
        EstadoTraslado.AUTORIZADO -> "Autorizado"
        EstadoTraslado.RECHAZADO -> "Rechazado"
    }

    fun accion(accion: AccionAuditoria): String = when (accion) {
        AccionAuditoria.CREAR -> "Creación"
        AccionAuditoria.ACTUALIZAR -> "Actualización"
        AccionAuditoria.DESACTIVAR -> "Desactivación"
        AccionAuditoria.CORREGIR -> "Corrección"
        AccionAuditoria.ANULAR -> "Anulación"
        AccionAuditoria.AUTORIZAR -> "Autorización"
        AccionAuditoria.RECHAZAR -> "Rechazo"
        AccionAuditoria.LOGIN -> "Inicio de sesión"
        AccionAuditoria.RESOLVER_CONFLICTO -> "Conflicto resuelto"
        AccionAuditoria.SYNC -> "Sincronización"
        AccionAuditoria.CERRAR_JORNADA -> "Cierre de jornada"
        AccionAuditoria.REABRIR_JORNADA -> "Reapertura de jornada"
    }

    fun entidad(entidad: String): String = when (entidad.lowercase()) {
        "entrega" -> "Entrega"
        "usuario" -> "Cuenta"
        "proveedor" -> "Proveedor"
        "jornada" -> "Jornada"
        "traslado", "traslado_zona" -> "Traslado"
        "liquidacion" -> "Liquidación"
        "zona" -> "Zona"
        "vehiculo" -> "Vehículo"
        "control_calidad", "calidad" -> "Análisis de calidad"
        else -> entidad.replaceFirstChar { it.uppercase() }
    }

    /** "litros=18.0;tachos=1" → "litros 18.0 · tachos 1"; los valores se muestran tal como se guardaron. */
    fun valores(texto: String): String = texto.split(';').filter { it.isNotBlank() }.joinToString(" · ") { par ->
        val partes = par.split('=', limit = 2)
        if (partes.size == 2) "${partes[0].trim()} ${partes[1].trim()}" else par.trim()
    }
}

fun coloresSyncAdmin(estado: SyncState): Pair<Color, Color> = when (estado) {
    SyncState.PENDING -> AdminColor.ambarTexto to AdminColor.ambarSuave
    SyncState.SYNCING -> AdminColor.azul to AdminColor.azulSuave
    SyncState.SYNCED -> AdminColor.verde to AdminColor.verdeSuave
    SyncState.ERROR -> AdminColor.rojo to AdminColor.rojoSuave
    SyncState.CONFLICT -> AdminColor.morado to AdminColor.moradoSuave
}

/** Insignia única de una entrega: una anulada siempre dice "Anulada", sin importar su sincronización. */
@Composable
fun EtiquetaEntrega(entrega: Entrega, modifier: Modifier = Modifier, larga: Boolean = false) {
    if (entrega.anulada) {
        AdminEtiqueta(TextosEstado.ANULADA, AdminColor.rojo, AdminColor.rojoSuave, modifier)
    } else {
        val (color, fondo) = coloresSyncAdmin(entrega.syncState)
        AdminEtiqueta(if (larga) TextosEstado.larga(entrega.syncState) else TextosEstado.corta(entrega.syncState), color, fondo, modifier)
    }
}
