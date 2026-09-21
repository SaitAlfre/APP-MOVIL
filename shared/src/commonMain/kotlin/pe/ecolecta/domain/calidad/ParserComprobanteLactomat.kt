package pe.ecolecta.domain.calidad

import kotlinx.datetime.LocalDateTime
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toInstant
import pe.ecolecta.domain.model.LecturaCalidad

/** Convierte el texto OCR de comprobantes LACTOMAT en valores editables; nunca guarda sin revisión. */
object ParserComprobanteLactomat {
    // Varias marcas de analizador (LACTOMAT/Lactoscan/Ekomilk, etc.) imprimen en español o en inglés
    // según el firmware; se incluyen ambas formas y abreviaturas usuales para no depender de una sola.
    private val nombresPorClave = mapOf(
        "temperatura" to listOf("Temperatura", "Temp", "Temperature"),
        "grasa" to listOf("Grasa", "Fat"),
        "sng" to listOf("SNG", "SNF", "S.N.G", "S.N.F"),
        "densidad" to listOf("Densidad", "Density"),
        "proteina" to listOf("Proteina", "Proteína", "Protein"),
        "lactosa" to listOf("Lactosa", "Lactose"),
        "sales" to listOf("Sales", "Salts", "Salt"),
        "solidos" to listOf("Total solidos", "Sólidos totales", "Total sólidos", "Total Solids", "Solids"),
        "agua" to listOf("Agua anadida", "Agua añadida", "Added Water", "Water"),
        "congelacion" to listOf("Punto cong", "Freezing Point", "Freezing", "F.P"),
        "ph" to listOf("pH"),
    )

    /** Une la etiqueta con el primer número que aparece después de ella, sin cruzar de línea ni de signo. */
    private fun patronDe(nombre: String) = Regex("(?i)${Regex.escape(nombre)}[^0-9\\n-]*(-?\\d+(?:[.,]\\d+)?)")

    private fun valorDe(texto: String, nombres: List<String>): Double? {
        for (nombre in nombres) {
            patronDe(nombre).find(texto)?.groupValues?.get(1)?.replace(',', '.')?.toDoubleOrNull()?.let { return it }
        }
        return null
    }

    private fun tokenDespues(texto: String, vararg nombres: String): String? {
        for (nombre in nombres) {
            val patron = Regex("(?i)\\b${Regex.escape(nombre)}\\s*[:.]?\\s*([A-Za-z0-9._-]+)")
            patron.find(texto)?.groupValues?.getOrNull(1)?.takeIf { it.isNotBlank() }?.let { return it }
        }
        return null
    }

    fun parsear(texto: String): LecturaCalidad = LecturaCalidad(
        serialAnalizador = tokenDespues(texto, "S/N", "SN", "Serial"),
        modoAnalizador = tokenDespues(texto, "Mode", "Modo"),
        temperatura = valorDe(texto, nombresPorClave.getValue("temperatura")),
        grasa = valorDe(texto, nombresPorClave.getValue("grasa")),
        sng = valorDe(texto, nombresPorClave.getValue("sng")),
        densidad = valorDe(texto, nombresPorClave.getValue("densidad")),
        proteina = valorDe(texto, nombresPorClave.getValue("proteina")),
        lactosa = valorDe(texto, nombresPorClave.getValue("lactosa")),
        sales = valorDe(texto, nombresPorClave.getValue("sales")),
        solidosTotales = valorDe(texto, nombresPorClave.getValue("solidos")),
        aguaAnadida = valorDe(texto, nombresPorClave.getValue("agua")),
        puntoCongelacion = valorDe(texto, nombresPorClave.getValue("congelacion")),
        ph = valorDe(texto, nombresPorClave.getValue("ph")),
        fechaHoraEpochMs = fechaHora(texto),
    )

    /** Claves cuya etiqueta aparece más de una vez seguida de un número: la lectura es ambigua y conviene revisarla. */
    fun dudosos(texto: String): Set<String> = nombresPorClave.filterValues { nombres ->
        nombres.any { nombre -> patronDe(nombre).findAll(texto).count() > 1 }
    }.keys

    /** Intenta leer una fecha y hora del comprobante (dd/MM/yyyy y HH:mm, con separadores comunes). Best effort. */
    private fun fechaHora(texto: String): Long? {
        val fecha = Regex("""(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2,4})""").find(texto) ?: return null
        val dia = fecha.groupValues[1].toIntOrNull() ?: return null
        val mes = fecha.groupValues[2].toIntOrNull() ?: return null
        val anioTexto = fecha.groupValues[3]
        val anio = anioTexto.toIntOrNull()?.let { if (it < 100) it + 2000 else it } ?: return null
        val hora = Regex("""(\d{1,2}):(\d{2})""").find(texto, fecha.range.last)
        val horas = hora?.groupValues?.get(1)?.toIntOrNull() ?: 0
        val minutos = hora?.groupValues?.get(2)?.toIntOrNull() ?: 0
        return try {
            LocalDateTime(anio, mes, dia, horas, minutos).toInstant(TimeZone.currentSystemDefault()).toEpochMilliseconds()
        } catch (e: IllegalArgumentException) {
            null
        }
    }
}
