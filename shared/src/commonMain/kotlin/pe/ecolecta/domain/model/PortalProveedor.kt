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

/** Prefijo del id de una liquidación recibida del panel web (fuente oficial de liquidaciones). */
const val PREFIJO_PAGO_SERVIDOR = "web-"

val PagoProveedor.delServidor: Boolean get() = id.startsWith(PREFIJO_PAGO_SERVIDOR)

/**
 * Emitida = el administrador ya fijó su importe, con alguno de estos estados reales:
 * - APROBADA: aprobada por el administrador en el celular (Reportes), aún sin pagar.
 * - PENDIENTE: generada en el panel web con monto definitivo (no se edita ni se anula), aún sin pagar.
 *   El panel no tiene un paso de "publicación" aparte de generarla (ver web/.../MovilController).
 * - PAGADA: pago registrado.
 * Un borrador, una anulada o un estado desconocido nunca se muestran como importe vigente.
 */
val PagoProveedor.emitida: Boolean
    get() = estado.uppercase() in setOf("APROBADA", "APPROVED", "PENDIENTE", "PAGADA", "PAGADO", "PAID")

/**
 * Liquidaciones que ve el proveedor. Si el panel web publicó una liquidación para el mismo periodo que
 * una generada en un celular, se muestra solo la del panel: es la fuente oficial y así no aparecen dos
 * importes para la misma semana.
 */
fun pagosVisibles(pagos: List<PagoProveedor>): List<PagoProveedor> {
    val periodosDelServidor = pagos.filter { it.delServidor }.map { it.desde to it.hasta }.toSet()
    return pagos
        .filter { it.delServidor || (it.desde to it.hasta) !in periodosDelServidor }
        .sortedWith(compareByDescending<PagoProveedor> { it.hasta }.thenByDescending { it.desde })
}

/**
 * La liquidación emitida más reciente: la que Inicio resume, con su estado real (pendiente de pago,
 * aprobada o pagada). Las liquidaciones se emiten para semanas YA cerradas (nunca para la semana en
 * curso), así que no se busca la semana actual.
 */
fun ultimaLiquidacionEmitida(pagos: List<PagoProveedor>): PagoProveedor? =
    pagosVisibles(pagos).firstOrNull { it.emitida }
