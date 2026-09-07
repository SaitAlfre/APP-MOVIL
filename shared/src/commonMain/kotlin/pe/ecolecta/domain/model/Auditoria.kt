package pe.ecolecta.domain.model

data class Auditoria(
    val id: String,
    val entidad: String,
    val entidadId: String,
    val accion: AccionAuditoria,
    val valorAntes: String?,
    val valorDespues: String?,
    val motivo: String?,
    val usuarioId: String,
    val ocurridoEn: Long,
    val deviceId: String,
    val syncState: SyncState,
)
