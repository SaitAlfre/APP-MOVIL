package pe.ecolecta.domain.model

import kotlinx.datetime.*
import kotlinx.serialization.Serializable
import kotlin.math.roundToLong

/** La semana contable se determina en Lima, independientemente del teléfono. */
fun inicioSemanaProveedor(fecha: LocalDate): LocalDate =
    fecha.minus(DatePeriod(days = (fecha.dayOfWeek.ordinal - DayOfWeek.THURSDAY.ordinal + 7) % 7))

fun validarReclamo(litros: String, motivo: String, descripcion: String): Double {
    val valor = litros.trim().replace(',', '.').toDoubleOrNull()
    require(valor != null && valor.isFinite() && valor > 0 && valor <= 18500) { "Ingresa litros válidos, mayores a cero y hasta 18 500." }
    require(motivo in motivosReclamo) { "Selecciona un motivo." }
    require(descripcion.trim().length in 10..2000) { "Describe el problema con entre 10 y 2000 caracteres." }
    require(kotlin.math.abs(valor * 100 - (valor * 100).roundToLong()) < 0.000001) { "Usa como máximo dos decimales." }
    return valor
}

val motivosReclamo = listOf("Litros mal registrados", "Entrega no registrada", "Tachos incorrectos", "Otro motivo")

@Serializable
data class SolicitudProveedor(
    val id: String,
    val proveedorId: String,
    val tipo: String,
    val referenciaId: String?,
    val litros: Double?,
    val motivo: String,
    val descripcion: String,
    val evidencia: String? = null,
    val creadaEn: Long,
    val estado: String = "PENDIENTE_ENVIO",
)

/** Solo contiene liquidaciones recibidas de una fuente autorizada, nunca estimaciones de pago. */
@Serializable
data class PagoProveedor(
    val id: String,
    val proveedorId: String,
    val desde: String,
    val hasta: String,
    val litros: Double,
    val precio: Double,
    val bruto: Double,
    val descuento: Double,
    val total: Double,
    val estado: String,
    val fechaPago: String? = null,
) {
    fun comprobante(nombre: String, codigo: String): String = """
        ECOLACTEA DIGITAL — COMPROBANTE DE LIQUIDACIÓN
        Referencia: $id
        Proveedor: $nombre ($codigo)
        Periodo: $desde al $hasta
        Litros: $litros
        Precio por litro: S/ $precio
        Importe bruto: S/ $bruto
        Descuentos: S/ $descuento
        Total: S/ $total
        Estado: $estado
        Fecha de pago: ${fechaPago ?: "Sin pago registrado"}
    """.trimIndent()
}
