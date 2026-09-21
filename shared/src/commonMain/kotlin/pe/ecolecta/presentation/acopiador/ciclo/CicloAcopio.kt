package pe.ecolecta.presentation.acopiador.ciclo

import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Instant

/**
 * # PLACEHOLDER — no es lógica de negocio real
 *
 * El diseño de móvil organiza el trabajo del acopiador en "ciclos" de 6 días con pago el último
 * día ("Ciclo 03 · Día 3 de 6 · Pago: 08 sep."). Ese concepto **todavía no existe en el dominio**:
 * no hay modelo `Ciclo`, ni tabla, ni caso de uso, ni sincronización. Este archivo deriva un ciclo
 * a partir de la fecha para que las pantallas nuevas se puedan ver y evaluar, y es deliberadamente
 * el **único** lugar del que se leen esos datos.
 *
 * Cuando el ciclo se implemente de verdad (con su fecha de inicio real, su zona y su liquidación),
 * se borra este archivo y [CicloAcopio] pasa a venir de un caso de uso. Ninguna pantalla necesita
 * cambiar de forma: todas reciben ya un [CicloAcopio] armado.
 *
 * Mientras tanto, la numeración sale de contar bloques de 6 días desde el 1 de enero, que es una
 * convención inventada aquí y no coincide con ningún ciclo real de Ecolecta Huata.
 */
data class CicloAcopio(
    val numero: Int,
    val zonaNombre: String,
    val inicio: LocalDate,
    val fin: LocalDate,
    val dia: Int,
    val totalDias: Int,
) {
    /** "CICLO 03 · ZONA COLLANA" — encabezado de la tarjeta de contexto. */
    val titulo: String get() = "CICLO ${numero.toString().padStart(2, '0')} · ZONA ${zonaNombre.uppercase()}"

    /** "03–08 de septiembre · Día 3 de 6". */
    val detalle: String
        get() = "${inicio.day.dos()}–${fin.day.dos()} de ${nombreMes(fin.monthNumber)} · Día $dia de $totalDias"

    /** "Ciclo 03 · Collana · Día 3 de 6" — subtítulo compacto de la lista. */
    val resumenCorto: String get() = "Ciclo ${numero.toString().padStart(2, '0')} · $zonaNombre · Día $dia de $totalDias"

    /** "Ciclo 03 · 03–08 sep. · Pago: 08 sep." — subtítulo del registro semanal. */
    val resumenPago: String
        get() = "Ciclo ${numero.toString().padStart(2, '0')} · ${inicio.day.dos()}–${fin.day.dos()} ${mesCorto(fin.monthNumber)} · " +
            "Pago: ${fin.day.dos()} ${mesCorto(fin.monthNumber)}"

    /** "03 sep. 2026" — fechas sueltas para las tarjetas de inicio/fin de la jornada. */
    val inicioLargo: String get() = "${inicio.day.dos()} ${mesCorto(inicio.monthNumber)} ${inicio.year}"
    val finLargo: String get() = "${fin.day.dos()} ${mesCorto(fin.monthNumber)} ${fin.year}"

    /** Las 6 fechas del ciclo, para las columnas de la tabla semanal. */
    val dias: List<LocalDate> get() = (0 until totalDias).map { inicio.plusDays(it) }
}

private const val DIAS_POR_CICLO = 6

/**
 * Deriva el ciclo que contiene [fecha] para la zona indicada. Ver la advertencia de arriba: la
 * numeración es una convención provisional, no un dato del sistema.
 */
fun cicloSimuladoDe(fecha: LocalDate, zonaNombre: String): CicloAcopio {
    val indice = (fecha.dayOfYear - 1) / DIAS_POR_CICLO
    val dia = (fecha.dayOfYear - 1) % DIAS_POR_CICLO + 1
    val inicio = fecha.plusDays(-(dia - 1))
    return CicloAcopio(
        numero = indice + 1,
        zonaNombre = zonaNombre,
        inicio = inicio,
        fin = inicio.plusDays(DIAS_POR_CICLO - 1),
        dia = dia,
        totalDias = DIAS_POR_CICLO,
    )
}

/** La fecha local de un instante, para ubicar una entrega en su día dentro del ciclo. */
fun fechaLocalDe(epochMs: Long): LocalDate =
    Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(TimeZone.currentSystemDefault()).date

private fun LocalDate.plusDays(dias: Int): LocalDate =
    LocalDate.fromEpochDays(this.toEpochDays() + dias)

private fun Int.dos(): String = toString().padStart(2, '0')

private fun nombreMes(mes: Int): String = when (mes) {
    1 -> "enero"; 2 -> "febrero"; 3 -> "marzo"; 4 -> "abril"; 5 -> "mayo"; 6 -> "junio"
    7 -> "julio"; 8 -> "agosto"; 9 -> "septiembre"; 10 -> "octubre"; 11 -> "noviembre"; else -> "diciembre"
}

private fun mesCorto(mes: Int): String = when (mes) {
    1 -> "ene."; 2 -> "feb."; 3 -> "mar."; 4 -> "abr."; 5 -> "may."; 6 -> "jun."
    7 -> "jul."; 8 -> "ago."; 9 -> "sep."; 10 -> "oct."; 11 -> "nov."; else -> "dic."
}
