package pe.ecolecta.domain.calidad

import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.EvaluacionCalidad
import pe.ecolecta.domain.model.LecturaCalidad

/** Límites operativos iniciales. Deben poder ajustarse cuando la empresa valide su protocolo. */
object EvaluadorCalidad {
    fun evaluar(lectura: LecturaCalidad): EvaluacionCalidad {
        val alertas = mutableListOf<String>()
        var rechazar = false
        var repetir = false

        fun revisar(nombre: String, valor: Double?, minimo: Double, maximo: Double) {
            if (valor == null) return
            if (!valor.isFinite()) {
                alertas += "$nombre contiene un valor inválido; repetir el análisis."
                repetir = true
            } else if (valor !in minimo..maximo) {
                alertas += "$nombre fuera del rango operativo ($minimo–$maximo)."
            }
        }

        revisar("Temperatura", lectura.temperatura, 0.0, 8.0)
        revisar("Grasa", lectura.grasa, 3.0, 6.0)
        revisar("SNG", lectura.sng, 8.2, 10.0)
        revisar("Densidad", lectura.densidad, 1.028, 1.034)
        revisar("Proteína", lectura.proteina, 2.8, 4.2)
        revisar("Lactosa", lectura.lactosa, 4.2, 5.5)
        revisar("Sólidos totales", lectura.solidosTotales, 11.2, 16.0)
        revisar("Punto de congelación", lectura.puntoCongelacion, -0.555, -0.515)
        revisar("pH", lectura.ph, 6.5, 6.8)

        lectura.aguaAnadida?.let {
            if (it < 0.0 || it > 100.0) {
                alertas += "Agua añadida contiene un valor imposible; repetir el análisis."
                repetir = true
            } else if (it > 0.0) {
                alertas += "El analizador reporta posible agua añadida ($it%)."
                rechazar = true
            }
        }

        val medidos = listOf(
            lectura.temperatura, lectura.grasa, lectura.sng, lectura.densidad,
            lectura.proteina, lectura.lactosa, lectura.solidosTotales,
            lectura.aguaAnadida, lectura.puntoCongelacion, lectura.ph,
        ).count { it != null }
        if (medidos < 3) {
            alertas += "Hay muy pocos parámetros para validar la muestra; completa o repite el análisis."
            repetir = true
        }

        val estado = when {
            repetir -> EstadoControlCalidad.REPETIR
            rechazar -> EstadoControlCalidad.RECHAZADO
            alertas.isNotEmpty() -> EstadoControlCalidad.OBSERVADO
            else -> EstadoControlCalidad.APROBADO
        }
        return EvaluacionCalidad(estado, alertas)
    }
}

