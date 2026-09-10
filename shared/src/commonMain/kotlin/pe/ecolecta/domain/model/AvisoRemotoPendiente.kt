package pe.ecolecta.domain.model

/**
 * Aviso de "detener seguimiento" o "cerrar jornada" que no se pudo publicar en Firestore (sin conexión
 * o falla de red). Se persiste localmente y se reintenta cuando la app vuelva a ejecutarse con
 * conexión (§ Grupo 5, punto 1) — no hay garantía de reintento con la app completamente cerrada.
 *
 * [jornadaAbierta] en `null` es "detener seguimiento" (nunca toca la jornada); en `false` es
 * "cerrar jornada" (que también implica seguimientoActivo=false).
 */
data class AvisoRemotoPendiente(
    val usuarioId: String,
    val zonaId: String,
    val jornadaId: String,
    val jornadaAbiertaEn: Long,
    val secuenciaEn: Long,
    val jornadaAbierta: Boolean?,
    val creadoEn: Long,
)
