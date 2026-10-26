package pe.ecolecta.domain.model

import pe.ecolecta.domain.ProveedorInvalidoException

data class Proveedor(
    val id: String,
    val codigo: String,
    val nombres: String,
    val dni: String,
    val telefono: String?,
    val direccion: String?,
    val zonaId: String,
    val tachos: Int,
    val capacidadTachoL: Double,
    val estado: EstadoProveedor,
    val updatedAt: Long,
    val syncState: SyncState,
    val usuarioId: String? = null,
    val dueno: String? = null,
) {
    val capacidadTotalL: Double get() = tachos * capacidadTachoL

    companion object {
        fun crear(
            id: String,
            codigo: String,
            nombres: String,
            dni: String,
            telefono: String?,
            direccion: String?,
            zonaId: String,
            tachos: Int = 1,
            capacidadTachoL: Double = 40.0,
            estado: EstadoProveedor = EstadoProveedor.ACTIVO,
            updatedAt: Long,
            syncState: SyncState = SyncState.SYNCED,
            usuarioId: String? = null,
        ): Result<Proveedor> {
            if (codigo.isBlank()) return Result.failure(ProveedorInvalidoException.CodigoVacio)
            if (nombres.isBlank()) return Result.failure(ProveedorInvalidoException.NombresVacios)
            if (dni.isBlank()) return Result.failure(ProveedorInvalidoException.DniVacio)
            for ((valor, maximo, campo) in listOf(
                Triple(codigo, 50, "El código"), Triple(nombres, 150, "El nombre"),
                Triple(dni, 20, "El documento"), Triple(telefono.orEmpty(), 30, "El teléfono"),
                Triple(direccion.orEmpty(), 255, "La dirección"),
            )) {
                if (valor.trim().length > maximo) return Result.failure(IllegalArgumentException("$campo admite hasta $maximo caracteres."))
            }
            if (tachos !in 1..1000) return Result.failure(ProveedorInvalidoException.TachosInvalidos)
            if (!capacidadTachoL.isFinite() || capacidadTachoL < 0.01) return Result.failure(ProveedorInvalidoException.CapacidadInvalida)
            return Result.success(
                Proveedor(
                    id = id,
                    codigo = codigo.trim(),
                    nombres = nombres.trim(),
                    dni = dni.trim(),
                    telefono = telefono?.trim()?.ifBlank { null },
                    direccion = direccion?.trim()?.ifBlank { null },
                    zonaId = zonaId,
                    tachos = tachos,
                    capacidadTachoL = capacidadTachoL,
                    estado = estado,
                    updatedAt = updatedAt,
                    syncState = syncState,
                    usuarioId = usuarioId,
                ),
            )
        }
    }
}
