package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.navegacion.Pantalla

@Composable fun InicioProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        Column(Modifier.fillMaxWidth().background(ProveedorAzul).padding(start = 20.dp, top = 16.dp, end = 20.dp, bottom = 32.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    TextoProveedor("Semana: ${s.inicioSemana} – ${s.finSemana}", 12, Color.White.copy(alpha = .75f))
                    TextoProveedor(s.proveedor!!.nombres, 20, Color.White, true)
                    TextoProveedor("${s.proveedor.codigo} · Zona ${s.zona}", 12, Color.White.copy(alpha = .75f))
                }
                EtiquetaProveedor("Datos locales", true)
            }
            listOf(
                listOf("Litros hoy" to "${decimalProveedor(s.litrosHoy)} L", "Litros semana" to "${decimalProveedor(s.litrosSemana)} L"),
                listOf("Precio por litro" to (s.pagoSemana?.let { "S/ ${decimalProveedor(it.precio)}" } ?: "Por confirmar"), "Total estimado" to (s.pagoSemana?.let { "S/ ${decimalProveedor(it.total)}" } ?: "Por confirmar")),
            ).forEach { fila -> Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                fila.forEach { (etiqueta, valor) -> TarjetaProveedor(Modifier.weight(1f), Color.White.copy(alpha = .15f)) {
                    TextoProveedor(etiqueta, 11, Color.White.copy(alpha = .8f)); TextoProveedor(valor, 20, Color.White, true)
                } }
            } }
        }
        Column(Modifier.padding(horizontal = 16.dp).offset(y = (-12).dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaProveedor(Modifier.weight(1f), accion = { navegar(Pantalla.ProveedorCalidad) }) {
                    TextoProveedor("ÚLTIMA CALIDAD", 11, ProveedorGris, true)
                    val ultima = s.calidad.firstOrNull()
                    TextoProveedor(ultima?.let { etiquetaCalidad(it.estado) } ?: "Sin análisis", 14, ProveedorVerde, true)
                    TextoProveedor(ultima?.let { "pH ${it.ph ?: "—"} · Gr ${it.grasa ?: "—"}%" } ?: "Aún no registrada", 12, ProveedorGris)
                }
                TarjetaProveedor(Modifier.weight(1f), accion = { navegar(Pantalla.ProveedorPagos) }) {
                    TextoProveedor("PAGO", 11, ProveedorGris, true)
                    TextoProveedor(s.pagoSemana?.let { etiquetaPago(it.estado) } ?: "Sin liquidación", 14, Color(0xFF9C720A), true)
                    TextoProveedor(s.pagoSemana?.let { "S/ ${decimalProveedor(it.total)}" } ?: "Pendiente de publicación", 12, ProveedorGris)
                }
            }
            TarjetaProveedor(color = Color(0xFFE8F4EC)) {
                TextoProveedor("INFORMACIÓN", 11, ProveedorVerde, true)
                TextoProveedor("Tu semana de acopio", 14, bold = true)
                TextoProveedor("Consulta tus entregas de jueves a miércoles. Los importes se muestran cuando exista una liquidación registrada.", 13, ProveedorGris)
            }
            if(s.proveedor?.estado != EstadoProveedor.ACTIVO) EtiquetaProveedor("Cuenta ${s.proveedor?.estado?.name?.lowercase()}", true)
            TextoProveedor("ACCIONES", 13, ProveedorGris, true)
            listOf(
                Triple("🥛", "Mis entregas", Pantalla.ProveedorEntregas),
                Triple("🔬", "Mi calidad", Pantalla.ProveedorCalidad),
                Triple("💰", "Mis pagos", Pantalla.ProveedorPagos),
                Triple("📲", "Mostrar QR", Pantalla.ProveedorMiQr),
                Triple("📝", "Presentar reclamo", Pantalla.ProveedorReclamos),
                Triple("🚛", "Solicitar traslado", Pantalla.ProveedorTraslado),
            ).chunked(2).forEach { fila -> Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                fila.forEach { (icono, nombre, destino) -> TarjetaProveedor(Modifier.weight(1f), accion = { navegar(destino) }) {
                    TextoProveedor(icono, 22); TextoProveedor(nombre, 13, bold = true)
                } }
            } }
            TextButton(onClick = { navegar(Pantalla.ProveedorSolicitudes) }) { Text("Mis solicitudes (${s.solicitudes.size})") }
        }
    }
}

@Composable fun EntregasProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    var todas by rememberSaveable { mutableStateOf(false) }
    PaginaProveedor("Mis entregas", "Total semana: ${decimalProveedor(s.litrosSemana)} L", { navegar(Pantalla.ProveedorHome) }) {
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            FilterChip(!todas, { todas = false }, { Text("Esta semana") })
            FilterChip(todas, { todas = true }, { Text("Historial") })
        }
        val entregas = if(todas) s.entregas else s.semana
        if(entregas.isEmpty()) VacioProveedor("Sin entregas en este periodo", "Las entregas guardadas por el acopiador aparecerán aquí. Puedes consultar el historial o registrar un reclamo.")
        entregas.forEach { e -> TarjetaProveedor(accion = { navegar(Pantalla.ProveedorEntregaDetalle(e.id)) }) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    TextoProveedor(fechaProveedor(e.registradoEn), 13, ProveedorGris, true)
                    TextoProveedor("${decimalProveedor(e.litros)} L", 22, bold = true)
                    TextoProveedor("${e.tachos} tachos · ${s.usuarios[e.usuarioId] ?: "Acopiador no disponible"}", 13, ProveedorGris)
                }
                EtiquetaProveedor(if(e.anulada) "Anulada" else if(e.syncState == SyncState.SYNCED) "✓ Registrada" else "Pendiente", e.anulada || e.syncState != SyncState.SYNCED)
            }
        } }
        BotonProveedor("Presentar reclamo") { navegar(Pantalla.ProveedorReclamos) }
    }
}

@Composable fun DetalleProveedor(s: PortalProveedorState, id: String, navegar: (Pantalla) -> Unit) {
    PaginaProveedor("Detalle de entrega", volver = { navegar(Pantalla.ProveedorEntregas) }) {
        val e = s.entregas.firstOrNull { it.id == id }
        if(e == null) VacioProveedor("Entrega no disponible", "No se encontró esta entrega en tu historial.")
        else {
            TarjetaProveedor {
                TextoProveedor(fechaProveedor(e.registradoEn), 13, ProveedorGris)
                TextoProveedor("${decimalProveedor(e.litros)} L", 28, bold = true)
                TextoProveedor("${e.tachos} tachos · ${s.usuarios[e.usuarioId] ?: "Sin nombre de acopiador"}")
                TextoProveedor("Referencia: ${e.id}", 12, ProveedorGris)
                TextoProveedor(e.observaciones ?: "Sin observaciones", 14)
                EtiquetaProveedor(if(e.anulada) "Anulada" else if(e.syncState == SyncState.SYNCED) "Registrada" else "Pendiente de sincronización", e.anulada || e.syncState != SyncState.SYNCED)
            }
            TextoProveedor("El proveedor puede solicitar una revisión; los litros no se modifican desde esta pantalla.", 13, ProveedorGris)
            BotonProveedor("Presentar reclamo", !e.anulada) { navegar(Pantalla.ProveedorReclamos) }
        }
    }
}

@Composable fun PerfilProveedor(s: PortalProveedorState, vm: PortalProveedorViewModel, navegar: (Pantalla) -> Unit) {
    var cerrar by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        CabeceraProveedor("Perfil")
        Column(Modifier.fillMaxWidth().background(ProveedorAzul).padding(24.dp)) {
            TextoProveedor(s.proveedor!!.nombres, 18, Color.White, true)
            TextoProveedor("${s.proveedor.codigo} · Proveedor", 13, Color.White.copy(alpha = .8f))
        }
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            TarjetaProveedor {
                listOf("Zona" to s.zona, "Ruta" to (s.ruta?.zonaNombre ?: "Sin ruta descargada"), "Acopiador asignado" to (s.ruta?.acopiadorNombre ?: "Sin asignación descargada"), "Estado" to s.proveedor!!.estado.name.lowercase().replaceFirstChar { it.uppercase() }).forEach { (etiqueta, valor) ->
                    Row(Modifier.fillMaxWidth().padding(vertical = 10.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        TextoProveedor(etiqueta, 14, ProveedorGris, modifier = Modifier.weight(1f)); TextoProveedor(valor, 14, bold = true, modifier = Modifier.weight(1f))
                    }
                }
            }
            OutlinedButton(onClick = { navegar(Pantalla.ProveedorMiRuta) }, modifier = Modifier.fillMaxWidth()) { Text("Ver mi ruta de acopio") }
            OutlinedButton(onClick = { navegar(Pantalla.ProveedorSolicitudes) }, modifier = Modifier.fillMaxWidth()) { Text("Mis solicitudes") }
            OutlinedButton(onClick = vm::recargar, modifier = Modifier.fillMaxWidth()) { Text("Actualizar datos del dispositivo") }
            OutlinedButton(onClick = { cerrar = true }, modifier = Modifier.fillMaxWidth(), colors = ButtonDefaults.outlinedButtonColors(contentColor = ProveedorRojo)) { Text("Cerrar sesión") }
        }
    }
    if(cerrar) AlertDialog(onDismissRequest = { cerrar = false }, title = { Text("Cerrar sesión") }, text = { Text("Tus solicitudes guardadas se conservarán en este dispositivo.") }, confirmButton = { TextButton(onClick = vm::cerrarSesion) { Text("Cerrar sesión") } }, dismissButton = { TextButton(onClick = { cerrar = false }) { Text("Cancelar") } })
}
