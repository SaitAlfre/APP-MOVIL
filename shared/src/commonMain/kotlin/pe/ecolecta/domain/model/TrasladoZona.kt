package pe.ecolecta.domain.model

import pe.ecolecta.domain.TrasladoInvalidoException

data class TrasladoZona(
    val id: String,
    val proveedorId: String,
    val zonaOrigenId: String,
    val zonaDestinoId: String,
    val motivo: String?,
    val autorizadoPor: String?,
    val estado: EstadoTraslado,
    val creadoEn: Long,
) {
    companion object {
        fun crear(
            id: String,
            proveedorId: String,
            zonaOrigenId: String,
            zonaDestinoId: String,
            motivo: String?,
            creadoEn: Long,
        ): Result<TrasladoZona> {
            if (zonaOrigenId == zonaDestinoId) return Result.failure(TrasladoInvalidoException.ZonasIguales)
            return Result.success(
                TrasladoZona(
                    id = id,
                    proveedorId = proveedorId,
                    zonaOrigenId = zonaOrigenId,
                    zonaDestinoId = zonaDestinoId,
                    motivo = motivo?.trim()?.ifBlank { null },
                    autorizadoPor = null,
                    estado = EstadoTraslado.PENDIENTE,
                    creadoEn = creadoEn,
                ),
            )
        }
    }
}
