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
        Column(Modifier.fillMaxWidth().background(ProveedorTinta).padding(start = 20.dp, top = 16.dp, end = 20.dp, bottom = 32.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    TextoProveedor(s.ciclo.resumenCorto, 12, Color.White.copy(alpha = .75f))
                    TextoProveedor(s.proveedor!!.nombres, 20, Color.White, true)
                    TextoProveedor("${s.proveedor.codigo} · Zona ${s.zona}", 12, Color.White.copy(alpha = .75f))
                }
                EtiquetaProveedor(
                    when (s.conexion) {
                        ConexionPortal.EN_LINEA -> "Sincronizado"
                        ConexionPortal.SIN_CONEXION -> "Sin conexión"
                        ConexionPortal.CONECTANDO -> "Conectando"
                        ConexionPortal.NO_DISPONIBLE -> "Sin servidor"
                        ConexionPortal.NO_CONFIGURADA -> "Datos locales"
                    },
                    s.conexion != ConexionPortal.EN_LINEA,
                )
            }
            listOf(
                listOf("Litros hoy" to "${decimalProveedor(s.diaHoy?.totalLitros ?: 0.0)} L", "Litros del ciclo" to "${decimalProveedor(s.totalCiclo)} L"),
                listOf("Precio por litro" to (s.pagoSemana?.let { "S/ ${decimalProveedor(it.precio)}" } ?: "Por confirmar"), "Total estimado" to (s.pagoSemana?.let { "S/ ${decimalProveedor(it.total)}" } ?: "Por confirmar")),
            ).forEach { fila -> Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                fila.forEach { (etiqueta, valor) -> TarjetaProveedor(Modifier.weight(1f), Color.White.copy(alpha = .15f)) {
                    TextoProveedor(etiqueta, 11, Color.White.copy(alpha = .8f)); TextoProveedor(valor, 20, Color.White, true)
                } }
            } }
        }
        Column(Modifier.padding(horizontal = 16.dp).offset(y = (-12).dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            TarjetaHoyProveedor(s, navegar)
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaProveedor(Modifier.weight(1f), accion = { navegar(Pantalla.ProveedorCalidad) }) {
                    TextoProveedor("ÚLTIMA CALIDAD", 11, ProveedorGris, true)
                    val ultima = s.calidad.firstOrNull()
                    TextoProveedor(ultima?.let { etiquetaCalidad(it.estado) } ?: "Sin análisis", 14, ProveedorVerde, true)
                    TextoProveedor(ultima?.let { "pH ${it.ph ?: "—"} · Gr ${it.grasa ?: "—"}%" } ?: "Aún no registrada", 12, ProveedorGris)
                }
                TarjetaProveedor(Modifier.weight(1f), accion = { navegar(Pantalla.ProveedorPagos) }) {
                    TextoProveedor("PAGO", 11, ProveedorGris, true)
                    TextoProveedor(s.pagoSemana?.let { etiquetaPago(it.estado) } ?: "Sin liquidación", 14, Color(0xFF926B22), true)
                    TextoProveedor(s.pagoSemana?.let { "S/ ${decimalProveedor(it.total)}" } ?: "Pendiente de publicación", 12, ProveedorGris)
                }
            }
            ComunicadosProveedor()
            TarjetaProveedor(color = Color(0xFFE3F1E8)) {
                TextoProveedor("INFORMACIÓN", 11, ProveedorVerde, true)
                TextoProveedor("Tu ciclo de acopio", 14, bold = true)
                TextoProveedor("El recojo se organiza en ciclos de 6 días, con las mismas fechas que ve tu acopiador. Los pagos se liquidan por semana contable (jueves a miércoles) y los importes se muestran cuando exista una liquidación registrada.", 13, ProveedorGris)
            }
            if(s.proveedor?.estado != EstadoProveedor.ACTIVO) EtiquetaProveedor("Cuenta ${s.proveedor?.estado?.name?.lowercase()}", true)
            TextoProveedor("ACCIONES", 13, ProveedorGris, true)
            listOf(
                Triple("📅", "Mi ciclo", Pantalla.ProveedorMiCiclo),
                Triple("🥛", "Mis entregas", Pantalla.ProveedorEntregas),
                Triple("🔬", "Mi calidad", Pantalla.ProveedorCalidad),
                Triple("💰", "Mis pagos", Pantalla.ProveedorPagos),
                Triple("📲", "Mostrar QR", Pantalla.ProveedorMiQr),
                Triple("📝", "Reportar diferencia", Pantalla.ProveedorReclamos),
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
    PaginaProveedor("Mis entregas", "Total del ciclo: ${decimalProveedor(s.totalCiclo)} L", { navegar(Pantalla.ProveedorHome) }) {
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            FilterChip(!todas, { todas = false }, { Text("Este ciclo") })
            FilterChip(todas, { todas = true }, { Text("Historial") })
        }
        val entregas = if(todas) s.entregas else s.entregasCiclo
        if(entregas.isEmpty()) VacioProveedor("Sin entregas en este periodo", "Las entregas guardadas por el acopiador aparecerán aquí. Puedes consultar el historial o registrar un reclamo.")
        entregas.forEach { e -> TarjetaProveedor(accion = { navegar(Pantalla.ProveedorEntregaDetalle(e.id)) }) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(Modifier.weight(1f)) {
                    TextoProveedor(fechaProveedor(e.registradoEn), 13, ProveedorGris, true)
                    TextoProveedor("${decimalProveedor(e.litros)} L", 22, bold = true)
                    TextoProveedor("${e.tachos} tachos · ${s.usuarios[e.usuarioId] ?: "Acopiador no disponible"}", 13, ProveedorGris)
                }
                EtiquetaProveedor(if(e.anulada) "Anulada" else if(e.syncState == SyncState.SYNCED) "✓ Registrada" else "Por confirmar", e.anulada || e.syncState != SyncState.SYNCED)
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
        Column(Modifier.fillMaxWidth().background(ProveedorTinta).padding(24.dp)) {
            TextoProveedor(s.proveedor!!.nombres, 18, Color.White, true)
            TextoProveedor("${s.proveedor.codigo} · Proveedor", 13, Color.White.copy(alpha = .8f))
        }
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            TarjetaProveedor {
                listOf("Zona" to s.zona, "Último acopiador" to (s.entregas.firstOrNull()?.let { s.usuarios[it.usuarioId] } ?: "Sin registros todavía"), "Estado" to s.proveedor!!.estado.name.lowercase().replaceFirstChar { it.uppercase() }).forEach { (etiqueta, valor) ->
                    Row(Modifier.fillMaxWidth().padding(vertical = 10.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        TextoProveedor(etiqueta, 14, ProveedorGris, modifier = Modifier.weight(1f)); TextoProveedor(valor, 14, bold = true, modifier = Modifier.weight(1f))
                    }
                }
            }
            accionesPerfilProveedor.forEach { (texto, destino) ->
                OutlinedButton(onClick = { navegar(destino) }, modifier = Modifier.fillMaxWidth()) { Text(texto) }
            }
            OutlinedButton(onClick = vm::recargar, modifier = Modifier.fillMaxWidth()) { Text("Actualizar datos del dispositivo") }
            OutlinedButton(onClick = { cerrar = true }, modifier = Modifier.fillMaxWidth(), colors = ButtonDefaults.outlinedButtonColors(contentColor = ProveedorRojo)) { Text("Cerrar sesión") }
        }
    }
    if(cerrar) AlertDialog(onDismissRequest = { cerrar = false }, title = { Text("Cerrar sesión") }, text = { Text("Tus solicitudes guardadas se conservarán en este dispositivo.") }, confirmButton = { TextButton(onClick = vm::cerrarSesion) { Text("Cerrar sesión") } }, dismissButton = { TextButton(onClick = { cerrar = false }) { Text("Cancelar") } })
}

/** Avisos publicados por el administrador (Reportes → Publicar aviso); se muestran los dos más recientes. */
@Composable private fun ComunicadosProveedor() {
    val repositorio = org.koin.compose.koinInject<pe.ecolecta.domain.repository.ComunicadoRepository>()
    val comunicados by remember(repositorio) { repositorio.observarTodos() }.collectAsState(emptyList())
    comunicados.take(2).forEach { c ->
        TarjetaProveedor(color = Color(0xFFFFF0D1)) {
            TextoProveedor("📢 COMUNICADO", 11, Color(0xFF926B22), true)
            TextoProveedor(c.mensaje, 14)
            TextoProveedor(fechaProveedor(c.publicadoEn), 11, ProveedorGris)
        }
    }
}
