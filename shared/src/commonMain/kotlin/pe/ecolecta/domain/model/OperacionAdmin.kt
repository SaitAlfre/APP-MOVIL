package pe.ecolecta.domain.model

import kotlinx.datetime.DatePeriod
import kotlinx.datetime.LocalDate
import kotlinx.datetime.plus

/** Aviso que ADMIN publica para todos los proveedores; se muestra en el inicio del portal del proveedor. */
data class Comunicado(
    val id: String,
    val mensaje: String,
    val autorId: String,
    val autorNombre: String,
    val publicadoEn: Long,
)

/** Estados de una [SolicitudProveedor] a lo largo de su revisión administrativa. */
object EstadoSolicitud {
    const val PENDIENTE = "PENDIENTE_ENVIO"
    const val ATENDIDA = "ATENDIDA"
    const val APROBADA = "APROBADA"
    const val RECHAZADA = "RECHAZADA"

    fun estaPendiente(estado: String) = estado == PENDIENTE || estado == "PENDIENTE"
}

enum class TipoAlertaAdmin { CONFLICTO, RECLAMO, TRASLADO, CALIDAD }

/**
 * Asunto que requiere una decisión de ADMIN. [id] es estable entre recargas ("tipo:referencia"),
 * para poder ocultarla de la bandeja sin tocar el registro de origen.
 */
data class AlertaAdmin(
    val id: String,
    val tipo: TipoAlertaAdmin,
    val titulo: String,
    val descripcion: String,
    val ocurridaEn: Long,
    val referenciaId: String,
    val proveedorId: String?,
    val urgente: Boolean = false,
)

enum class EstadoLiquidacion { EN_CURSO, POR_APROBAR, APROBADA, PAGADA }

/** Semana contable (jueves a miércoles, como en el portal del proveedor) con sus litros y, si existe, su liquidación. */
data class LiquidacionSemanal(
    val desde: LocalDate,
    val litros: Double,
    val proveedores: Int,
    val entregas: Int,
    val precio: Double?,
    val total: Double?,
    val estado: EstadoLiquidacion,
) {
    val hasta: LocalDate get() = desde.plus(DatePeriod(days = 6))
}
