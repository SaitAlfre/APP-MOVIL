package pe.ecolecta.domain.model

import kotlinx.datetime.LocalDate

data class Jornada(
    val id: String,
    val usuarioId: String,
    val zonaId: String,
    val vehiculoId: String,
    val fecha: LocalDate,
    val abiertaEn: Long,
    val cerradaEn: Long?,
    val syncState: SyncState,
) {
    val estaAbierta: Boolean get() = cerradaEn == null
}
