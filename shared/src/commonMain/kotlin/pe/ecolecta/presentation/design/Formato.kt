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

/** Solo la hora ("06:38"), para listas donde la fecha ya la da el contexto de la pantalla. */
fun formatearHora(epochMs: Long): String {
    val ldt = Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(TimeZone.currentSystemDefault())
    return "${ldt.hour.dosDigitos()}:${ldt.minute.dosDigitos()}"
}

/** Hora en Perú ("06:38") para la lista de acopio: no depende de la zona horaria del teléfono. */
fun formatearHoraAcopio(epochMs: Long): String {
    val ldt = Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(pe.ecolecta.domain.acopio.ZONA_ACOPIO)
    return "${ldt.hour.dosDigitos()}:${ldt.minute.dosDigitos()}"
}

fun formatearFecha(epochMs: Long): String {
    val ldt = Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(TimeZone.currentSystemDefault())
    return "${ldt.day.toString().padStart(2, '0')}/${ldt.monthNumber.toString().padStart(2, '0')}/${ldt.year}"
}

/** "hace X min/h/d" a partir de un instante pasado — nunca presenta una posición antigua como si fuera reciente. */
fun formatearAntiguedad(epochMs: Long, ahoraMs: Long): String {
    val segundos = ((ahoraMs - epochMs) / 1000).coerceAtLeast(0)
    return when {
        segundos < 60 -> "hace instantes"
        segundos < 3600 -> "hace ${segundos / 60} min"
        segundos < 86_400 -> "hace ${segundos / 3600} h"
        else -> "hace ${segundos / 86_400} d"
    }
}
