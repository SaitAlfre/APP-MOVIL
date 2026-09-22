package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.calidad.CalidadBackHandler
import pe.ecolecta.presentation.navegacion.Pantalla

fun etiquetaCalidad(estado: EstadoControlCalidad) = when(estado) {
    EstadoControlCalidad.APROBADO -> "Aceptable"
    EstadoControlCalidad.OBSERVADO -> "Revisar"
    EstadoControlCalidad.RECHAZADO -> "Rechazado"
    EstadoControlCalidad.REPETIR -> "Repetir análisis"
}

@Composable fun CalidadProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    var seleccionado by rememberSaveable { mutableStateOf<String?>(null) }
    val control = s.calidad.firstOrNull { it.id == seleccionado }
    CalidadBackHandler(seleccionado != null) { seleccionado = null }
    PaginaProveedor("Mi calidad", "Últimos resultados LactoScan", { if(seleccionado != null) seleccionado = null else navegar(Pantalla.ProveedorHome) }) {
        if(control == null) {
            if(s.calidad.isEmpty()) VacioProveedor("Todavía no tienes análisis", "Los controles de calidad guardados para tu proveedor aparecerán aquí.")
            s.calidad.forEach { c -> TarjetaProveedor(accion = { seleccionado = c.id }) {
                TextoProveedor(fechaProveedor(c.registradoEn), 13, ProveedorGris)
                EtiquetaProveedor(etiquetaCalidad(c.estado), c.estado != EstadoControlCalidad.APROBADO)
                TextoProveedor("pH ${c.ph ?: "—"} · Grasa ${c.grasa ?: "—"}%", 12, ProveedorGris)
            } }
        } else {
            TextButton(onClick = { seleccionado = null }) { Text("Volver a la lista") }
            TarjetaProveedor {
                TextoProveedor(fechaProveedor(control.registradoEn), 13, ProveedorGris)
                EtiquetaProveedor(etiquetaCalidad(control.estado), control.estado != EstadoControlCalidad.APROBADO)
                TextoProveedor("Muestra ${control.codigoMuestra} · Equipo ${control.serialAnalizador ?: "Sin registrar"}", 12, ProveedorGris)
            }
            listOf(
                Triple("Temperatura", control.temperatura, "°C"), Triple("Grasa", control.grasa, "%"),
                Triple("SNG", control.sng, "%"), Triple("Densidad", control.densidad, ""),
                Triple("Proteína", control.proteina, "%"), Triple("Lactosa", control.lactosa, "%"),
                Triple("Sales", control.sales, "%"), Triple("Sólidos totales", control.solidosTotales, "%"),
                Triple("Agua añadida", control.aguaAnadida, "%"), Triple("Pto. congelación", control.puntoCongelacion, "°C"),
                Triple("pH", control.ph, ""),
            ).forEach { (nombre, valor, unidad) -> TarjetaProveedor {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    TextoProveedor(nombre); TextoProveedor(valor?.let { "$it $unidad" } ?: "No medido", 15, bold = true)
                }
            } }
            control.alertas.forEach { TextoProveedor(it, 13, ProveedorRojo) }
            control.observaciones?.let { TarjetaProveedor { TextoProveedor("Observaciones", bold = true); TextoProveedor(it) } }
            control.textoComprobante?.let { TarjetaProveedor { TextoProveedor("Lectura del comprobante", bold = true); TextoProveedor(it, 12, ProveedorGris) } }
        }
    }
}

fun etiquetaPago(estado: String) = when(estado.uppercase()) {
    "PAGADA", "PAGADO", "PAID" -> "Pagado"
    "APROBADA", "APPROVED" -> "Aprobado"
    "BORRADOR", "DRAFT" -> "Borrador"
    "ANULADA", "VOID" -> "Anulada"
    else -> "En revisión"
}

@Composable fun PagosProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    var mensaje by remember { mutableStateOf<String?>(null) }
    val guardar = recordarGuardarComprobante { mensaje = it }
    PaginaProveedor("Mis pagos", "Liquidaciones semanales", { navegar(Pantalla.ProveedorHome) }) {
        mensaje?.let { TextoProveedor(it, 13, ProveedorAzul) }
        if(s.pagos.isEmpty()) VacioProveedor("Aún no hay liquidaciones disponibles", "Este dispositivo no ha recibido liquidaciones del administrador. Tus litros están disponibles en Mis entregas; no representan un pago confirmado.")
        s.pagos.sortedByDescending { it.desde }.forEach { pago -> TarjetaProveedor {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    TextoProveedor("${pago.desde} – ${pago.hasta}", 12, ProveedorGris, true)
                    TextoProveedor("S/ ${decimalProveedor(pago.total)}", 22, bold = true)
                    TextoProveedor(pago.fechaPago ?: "Sin fecha de pago", 12, ProveedorGris)
                }
                EtiquetaProveedor(etiquetaPago(pago.estado), etiquetaPago(pago.estado) != "Pagado")
            }
            TarjetaProveedor(color = ProveedorFondo) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    listOf("Litros" to "${decimalProveedor(pago.litros)} L", "Precio" to "S/ ${decimalProveedor(pago.precio)}", "Descuento" to "S/ ${decimalProveedor(pago.descuento)}").forEach { (label, value) ->
                        Column(Modifier.weight(1f)) { TextoProveedor(value, 12, bold = true); TextoProveedor(label, 10, ProveedorGris) }
                    }
                }
            }
            if(etiquetaPago(pago.estado) == "Pagado") OutlinedButton(onClick = { guardar("comprobante-${pago.id.filter { it.isLetterOrDigit() }}.txt", pago.comprobante(s.proveedor!!.nombres, s.proveedor.codigo)) }, modifier = Modifier.fillMaxWidth()) { Text("Descargar comprobante") }
        } }
        if(s.pagos.isEmpty()) BotonProveedor("Ver mis entregas") { navegar(Pantalla.ProveedorEntregas) }
    }
}
