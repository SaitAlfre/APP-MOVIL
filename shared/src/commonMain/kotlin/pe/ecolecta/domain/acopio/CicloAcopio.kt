package pe.ecolecta.domain.acopio

import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.ecolecta.domain.Reloj
import kotlin.time.Instant

/**
 * Ciclo de acopio de 6 días que ya usaba el módulo móvil del acopiador ("Ciclo 03 · Día 3 de 6 ·
 * Pago: 08 sep."). Antes vivía en `presentation/acopiador/ciclo` y se calculaba con la zona horaria
 * del teléfono; ahora es la ÚNICA fuente de las fechas del ciclo para el acopiador (lista "Hoy",
 * tabla "Ciclo", selección de zona) y para el proveedor ("Mi ciclo"), y siempre se calcula con la
 * fecha de Perú ([ZONA_ACOPIO]) para que acopiador y proveedor vean exactamente las mismas 6 fechas.
 *
 * La numeración se conserva tal como estaba: bloques de 6 días contados desde el 1 de enero. No hay
 * todavía un modelo de ciclo con fecha de inicio administrada; si se crea, solo cambia [cicloAcopioDe].
 *
 * No interviene en liquidaciones: esas siguen usando su semana contable (ver
 * [pe.ecolecta.domain.model.inicioSemanaProveedor]) para no alterar periodos ya aprobados o pagados.
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

    /** "Ciclo 03 · 03–08 sep. · Pago: 08 sep." — subtítulo de la tabla del ciclo. */
    val resumenPago: String
        get() = "Ciclo ${numero.toString().padStart(2, '0')} · ${inicio.day.dos()}–${fin.day.dos()} ${mesCorto(fin.monthNumber)} · " +
            "Pago: ${fin.day.dos()} ${mesCorto(fin.monthNumber)}"

    /** "03 sep. 2026" — fechas sueltas para las tarjetas de inicio/fin de la jornada. */
    val inicioLargo: String get() = "${inicio.day.dos()} ${mesCorto(inicio.monthNumber)} ${inicio.year}"
    val finLargo: String get() = "${fin.day.dos()} ${mesCorto(fin.monthNumber)} ${fin.year}"

    /** Las 6 fechas del ciclo, en orden: son las columnas Día 1 … Día 6 de la hoja del acopiador. */
    val dias: List<LocalDate> get() = (0 until totalDias).map { inicio.plusDays(it) }

    fun contiene(fecha: LocalDate): Boolean = fecha in dias
}

const val DIAS_POR_CICLO = 6

/** El día de acopio se decide siempre con la hora de Perú, nunca con la zona horaria del teléfono. */
val ZONA_ACOPIO: TimeZone = TimeZone.of("America/Lima")

/** Fecha de acopio (Perú) de un instante: ubica una entrega o un "sin recojo" en su día del ciclo. */
fun fechaAcopioDe(epochMs: Long): LocalDate =
    Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(ZONA_ACOPIO).date

/** Hoy en Perú según [reloj]. */
fun hoyAcopio(reloj: Reloj): LocalDate = fechaAcopioDe(reloj.ahora().toEpochMilliseconds())

/** Ciclo de 6 días que contiene [fecha] (misma convención que usaba el módulo móvil). */
fun cicloAcopioDe(fecha: LocalDate, zonaNombre: String): CicloAcopio {
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

private fun LocalDate.plusDays(dias: Int): LocalDate = LocalDate.fromEpochDays(this.toEpochDays() + dias)

private fun Int.dos(): String = toString().padStart(2, '0')

private fun nombreMes(mes: Int): String = when (mes) {
    1 -> "enero"; 2 -> "febrero"; 3 -> "marzo"; 4 -> "abril"; 5 -> "mayo"; 6 -> "junio"
    7 -> "julio"; 8 -> "agosto"; 9 -> "septiembre"; 10 -> "octubre"; 11 -> "noviembre"; else -> "diciembre"
}

private fun mesCorto(mes: Int): String = when (mes) {
    1 -> "ene."; 2 -> "feb."; 3 -> "mar."; 4 -> "abr."; 5 -> "may."; 6 -> "jun."
    7 -> "jul."; 8 -> "ago."; 9 -> "sep."; 10 -> "oct."; 11 -> "nov."; else -> "dic."
}
