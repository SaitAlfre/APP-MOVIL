package pe.ecolecta.domain.calidad

import kotlin.math.abs

data class LineaTextoComprobante(val texto: String, val izquierda: Float, val centroY: Float, val alto: Float)

/** Une las columnas de etiquetas y valores que el OCR puede entregar como bloques separados. */
fun ordenarTextoComprobante(lineas: List<LineaTextoComprobante>): String {
    val filas = mutableListOf<MutableList<LineaTextoComprobante>>()
    lineas.filter { it.texto.isNotBlank() }.sortedBy { it.centroY }.forEach { linea ->
        val fila = filas.lastOrNull()
        val mismaFila = fila != null && abs(linea.centroY - fila.map { it.centroY }.average()) <=
            minOf(linea.alto, fila.minOf { it.alto }).coerceAtLeast(1f) * 0.6f
        if (mismaFila) fila!!.add(linea) else filas.add(mutableListOf(linea))
    }
    return filas.joinToString("\n") { fila -> fila.sortedBy { it.izquierda }.joinToString(" ") { it.texto } }
}
