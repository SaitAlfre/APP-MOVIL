package pe.ecolecta.presentation

import pe.ecolecta.domain.repository.EstadoCambiosServidor

/** Texto para el usuario sobre los cambios hechos aquí que aún no llegan al panel web; null si no hay ninguno. */
fun avisoCambiosServidor(estado: EstadoCambiosServidor): String? = when {
    estado.conError > 0 ->
        "El panel web no aceptó ${if (estado.conError == 1L) "1 cambio" else "${estado.conError} cambios"} hecho en este celular" +
            (estado.ultimoError?.let { ": $it" } ?: ".") + " Corrígelo en la app y se volverá a enviar."
    estado.pendientes > 0 ->
        "${if (estado.pendientes == 1L) "1 cambio" else "${estado.pendientes} cambios"} de este celular (cuentas, catálogos, jornadas, calidad...) " +
            "se enviará${if (estado.pendientes == 1L) "" else "n"} al panel web en cuanto haya conexión."
    else -> null
}
