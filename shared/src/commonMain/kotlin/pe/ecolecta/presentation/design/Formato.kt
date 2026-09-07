package pe.ecolecta.presentation.design

import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Instant

private fun Int.dosDigitos(): String = if (this < 10) "0$this" else this.toString()

fun formatearLitros(litros: Double): String {
    val redondeado = kotlin.math.round(litros * 10) / 10.0
    val texto = if (redondeado == redondeado.toLong().toDouble()) {
        "${redondeado.toLong()}.0"
    } else {
        redondeado.toString()
    }
    return "$texto L"
}

fun formatearFechaHora(epochMs: Long): String {
    val ldt = Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(TimeZone.currentSystemDefault())
    return "${ldt.day.toString().padStart(2, '0')}/${ldt.monthNumber.toString().padStart(2, '0')}/${ldt.year} " +
        "${ldt.hour.dosDigitos()}:${ldt.minute.dosDigitos()}"
}

fun formatearFecha(epochMs: Long): String {
    val ldt = Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(TimeZone.currentSystemDefault())
    return "${ldt.day.toString().padStart(2, '0')}/${ldt.monthNumber.toString().padStart(2, '0')}/${ldt.year}"
}
