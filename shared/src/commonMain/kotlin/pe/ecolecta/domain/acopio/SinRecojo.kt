package pe.ecolecta.domain.acopio

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.SyncState

/** Motivos seleccionables de "Sin recojo". El detalle libre es opcional en todos. */
enum class MotivoSinRecojo(val etiqueta: String) {
    SIN_LECHE("No tenía leche"),
    PROVEEDOR_NO_DISPONIBLE("Proveedor no disponible"),
    NO_SE_PUDO_LLEGAR("No se pudo llegar"),
    OTRO("Otro"),
    ;

    companion object {
        fun desde(nombre: String): MotivoSinRecojo = entries.firstOrNull { it.name == nombre } ?: OTRO
    }
}

/**
 * Marca explícita de que un proveedor NO tuvo recojo en [fecha]. Distinta de "pendiente" (celda
 * vacía, todavía no se visitó). Nunca se borra: deshacerla la deja con [deshecha] = true, así el
 * historial y la auditoría se conservan y la anulación también se sincroniza.
 */
data class MarcaSinRecojo(
    val id: String,
    val jornadaId: String,
    val proveedorId: String,
    val usuarioId: String,
    val zonaId: String,
    val fecha: LocalDate,
    val motivo: MotivoSinRecojo,
    val detalle: String?,
    val registradaEn: Long,
    val deshecha: Boolean,
    val deshechaEn: Long?,
    val syncState: SyncState,
    val syncError: String?,
    val intentos: Int,
    val updatedAt: Long,
) {
    val vigente: Boolean get() = !deshecha

    /** "No tenía leche · se fue al mercado" */
    val textoMotivo: String get() = listOfNotNull(motivo.etiqueta, detalle).joinToString(" · ")
}
