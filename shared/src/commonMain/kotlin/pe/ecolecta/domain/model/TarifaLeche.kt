package pe.ecolecta.domain.model

import kotlinx.datetime.LocalDate
import kotlinx.serialization.Serializable

@Serializable
data class TarifaLeche(val id: Long, val precio: Double, val desde: String, val hasta: String) {
    fun vigente(fecha: LocalDate): Boolean = runCatching {
        precio.isFinite() && precio > 0 && fecha >= LocalDate.parse(desde) && fecha <= LocalDate.parse(hasta)
    }.getOrDefault(false)
}

@Serializable
data class PreciosProveedor(val tarifas: List<TarifaLeche> = emptyList(), val recibidoEn: Long? = null) {
    fun vigente(fecha: LocalDate): TarifaLeche? = tarifas.filter { it.vigente(fecha) }.singleOrNull()
}
