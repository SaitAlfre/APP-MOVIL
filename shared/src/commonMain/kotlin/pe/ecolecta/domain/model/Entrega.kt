package pe.ecolecta.domain.model

import pe.ecolecta.domain.EntregaInvalidaException

data class Entrega(
    val id: String,
    val jornadaId: String,
    val proveedorId: String,
    val usuarioId: String,
    val zonaId: String,
    val vehiculoId: String,
    val litros: Double,
    val tachos: Int,
    val observaciones: String?,
    val registradoEn: Long,
    val deviceId: String,
    val loteId: String?,
    val anulada: Boolean,
    val syncState: SyncState,
    val syncError: String?,
    val intentos: Int,
    val updatedAt: Long,
    val litrosServidor: Double? = null,
    val tachosServidor: Double? = null,
    val motivoConflicto: String? = null,
) {
    companion object {
        fun crear(
            id: String,
            jornadaId: String,
            proveedorId: String,
            usuarioId: String,
            zonaId: String,
            vehiculoId: String,
            litros: Double,
            tachos: Int,
            observaciones: String?,
            registradoEn: Long,
            deviceId: String,
            loteId: String?,
        ): Result<Entrega> {
            if (litros <= 0.0) return Result.failure(EntregaInvalidaException.LitrosNoPositivos)
            if (tachos <= 0) return Result.failure(EntregaInvalidaException.TachosInvalidos)
            return Result.success(
                Entrega(
                    id = id,
                    jornadaId = jornadaId,
                    proveedorId = proveedorId,
                    usuarioId = usuarioId,
                    zonaId = zonaId,
                    vehiculoId = vehiculoId,
                    litros = litros,
                    tachos = tachos,
                    observaciones = observaciones?.trim()?.ifBlank { null },
                    registradoEn = registradoEn,
                    deviceId = deviceId,
                    loteId = loteId,
                    anulada = false,
                    syncState = SyncState.PENDING,
                    syncError = null,
                    intentos = 0,
                    updatedAt = registradoEn,
                ),
            )
        }
    }
}
