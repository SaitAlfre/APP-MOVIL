package pe.ecolecta.domain.calidad

import pe.ecolecta.domain.model.ControlCalidad
import pe.ecolecta.domain.model.LecturaCalidad
import kotlin.math.abs
import kotlin.math.round

data class ParametroCalidad(
    val clave: String,
    val nombre: String,
    val unidad: String,
    val minimo: Double,
    val maximo: Double,
    val ejemplo: String,
) {
    val referencia: String get() = if (minimo == maximo) "$minimo $unidad" else "$minimo a $maximo $unidad"
    fun correcto(valor: Double?) = valor != null && valor.isFinite() && valor in minimo..maximo
}

/** Referencias iniciales del proyecto; no constituyen una certificación de calidad. */
val parametrosCalidad = listOf(
    ParametroCalidad("temperatura", "Temperatura", "°C", 0.0, 8.0, "6.0"),
    ParametroCalidad("grasa", "Grasa", "%", 3.0, 6.0, "3.5"),
    ParametroCalidad("sng", "Sólidos no grasos", "%", 8.2, 10.0, "8.7"),
    ParametroCalidad("densidad", "Densidad", "g/cm³", 1.028, 1.034, "1.030"),
    ParametroCalidad("proteina", "Proteína", "%", 2.8, 4.2, "3.2"),
    ParametroCalidad("lactosa", "Lactosa", "%", 4.2, 5.5, "4.7"),
    ParametroCalidad("sales", "Sales", "%", 0.6, 0.85, "0.70"),
    ParametroCalidad("solidos", "Sólidos totales", "%", 11.2, 16.0, "12.2"),
    ParametroCalidad("agua", "Agua añadida", "%", 0.0, 0.0, "0.0"),
    ParametroCalidad("congelacion", "Punto de congelación", "°C", -0.555, -0.515, "-0.530"),
    ParametroCalidad("ph", "pH", "", 6.5, 6.8, "6.7"),
)

/** Único parámetro cuyo valor real puede ser negativo; el resto rechaza el signo como error de formato. */
fun permiteNegativo(clave: String) = clave == "congelacion"

fun String.numeroCalidad(): Double? = trim().replace(',', '.').toDoubleOrNull()?.takeIf { it.isFinite() }

/** Normaliza lecturas usuales de analizador (p. ej. grados Quevenne en densidad o depresión positiva en congelación). */
fun normalizarValorCalidad(clave: String, valor: Double?): Double? {
    if (valor == null || !valor.isFinite()) return null
    return when {
        clave == "densidad" && valor in 20.0..45.0 -> round((1.0 + valor / 1000.0) * 10000.0) / 10000.0
        clave == "densidad" && valor in 1000.0..1100.0 -> round((valor / 1000.0) * 10000.0) / 10000.0
        clave == "congelacion" && valor in 0.3..0.8 -> -valor
        clave == "congelacion" && abs(valor) in 300.0..800.0 -> -round(abs(valor) / 1000.0 * 10000.0) / 10000.0
        else -> valor
    }
}

fun LecturaCalidad.valores() = mapOf(
    "temperatura" to temperatura, "grasa" to grasa, "sng" to sng, "densidad" to densidad,
    "proteina" to proteina, "lactosa" to lactosa, "sales" to sales, "solidos" to solidosTotales,
    "agua" to aguaAnadida, "congelacion" to puntoCongelacion, "ph" to ph,
)

fun ControlCalidad.valores() = mapOf(
    "temperatura" to temperatura, "grasa" to grasa, "sng" to sng, "densidad" to densidad,
    "proteina" to proteina, "lactosa" to lactosa, "sales" to sales, "solidos" to solidosTotales,
    "agua" to aguaAnadida, "congelacion" to puntoCongelacion, "ph" to ph,
)
