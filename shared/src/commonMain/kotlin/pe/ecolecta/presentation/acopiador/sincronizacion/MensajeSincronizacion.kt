package pe.ecolecta.presentation.acopiador.sincronizacion

import pe.ecolecta.domain.usecase.sync.ResultadoSincronizacion

/**
 * Resumen de un "Sincronizar ahora" que dice lo que realmente pasó con cada grupo de registros. Solo
 * promete un reintento automático donde lo habrá (sin conexión, envío parcial); si falta enlazar una
 * cuenta, dice quién debe iniciar sesión, y los rechazos remiten al motivo guardado en cada registro.
 */
fun mensajeSincronizacion(r: ResultadoSincronizacion): String {
    if (r.total == 0) return "No hay registros pendientes de envío."
    val partes = buildList {
        if (r.enviados > 0) add("${r.enviados} enviado(s) y confirmado(s).")
        if (r.sinEnlazar > 0) {
            val quien = r.cuentasSinEnlazar.joinToString(", ").ifBlank { "la cuenta que los registró" }
            add(
                "${r.sinEnlazar} sin enviar porque la cuenta no está enlazada con el panel web: $quien debe " +
                    "iniciar sesión en este celular con conexión. Siguen guardados aquí.",
            )
        }
        if (r.sinConexion > 0) {
            add("${r.sinConexion} sin enviar: ${r.motivoSinConexion ?: "sin conexión con el panel web"}. Se reintentarán solos.")
        }
        if (r.parciales > 0) {
            add("${r.parciales} recibido(s) por el panel web, pero falta la copia para el celular del proveedor; se reintentará sin duplicar.")
        }
        if (r.fallidos > 0) add("${r.fallidos} rechazado(s) por el servidor: revisa el motivo en cada registro.")
    }
    return partes.joinToString(" ")
}
