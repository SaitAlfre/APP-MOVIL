package pe.ecolecta.presentation.proveedor

import pe.ecolecta.domain.model.EstadoSolicitud
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.drawWithContent
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.rememberGraphicsLayer
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import pe.ecolecta.domain.generarQrProveedor
import pe.ecolecta.domain.model.motivosReclamo
import pe.ecolecta.presentation.navegacion.Pantalla
import qrgenerator.qrkitpainter.rememberQrKitPainter
import qrgenerator.shareQrCodeImage

@Composable private fun SelectorProveedor(etiqueta: String, valor: String, opciones: List<Pair<String, String>>, cambiar: (String) -> Unit) {
    var abierto by remember { mutableStateOf(false) }
    Column {
        TextoProveedor(etiqueta, 13, bold = true)
        Box {
            OutlinedButton(onClick = { abierto = true }, modifier = Modifier.fillMaxWidth().heightIn(min = 48.dp), shape = RoundedCornerShape(12.dp)) {
                Text(opciones.firstOrNull { it.first == valor }?.second ?: "Selecciona una opción…")
            }
            DropdownMenu(expanded = abierto, onDismissRequest = { abierto = false }) {
                opciones.forEach { (id, nombre) -> DropdownMenuItem(text = { Text(nombre) }, onClick = { cambiar(id); abierto = false }) }
            }
        }
    }
}

@Composable private fun ResultadoSolicitud(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    TarjetaProveedor(color = Color(0xFFE8F4EC)) {
        TextoProveedor("✓ Solicitud guardada", 20, ProveedorVerde, true)
        TextoProveedor(s.confirmacion.orEmpty(), 14, ProveedorGris)
    }
    BotonProveedor("Ver mis solicitudes") { navegar(Pantalla.ProveedorSolicitudes) }
    OutlinedButton(onClick = { navegar(Pantalla.ProveedorHome) }, modifier = Modifier.fillMaxWidth()) { Text("Volver al inicio") }
}

@Composable fun ReclamoProveedor(s: PortalProveedorState, vm: PortalProveedorViewModel, navegar: (Pantalla) -> Unit) {
    var entregaId by rememberSaveable { mutableStateOf(s.entregas.firstOrNull { !it.anulada }?.id.orEmpty()) }
    var litros by rememberSaveable { mutableStateOf("") }
    var motivo by rememberSaveable { mutableStateOf("") }
    var descripcion by rememberSaveable { mutableStateOf("") }
    // La foto no se guarda en Bundle (Android limita su tamaño). Se persiste con la solicitud.
    var evidencia by remember { mutableStateOf<String?>(null) }
    var errorFoto by remember { mutableStateOf<String?>(null) }
    val adjuntar = recordarFotoProveedor({ evidencia = it; errorFoto = null }, { errorFoto = it })
    LaunchedEffect(Unit) { vm.limpiarMensaje() }
    PaginaProveedor("Presentar reclamo", volver = { navegar(Pantalla.ProveedorHome) }) {
        if(s.confirmacion != null) ResultadoSolicitud(s, navegar)
        else {
            SelectorProveedor("Motivo del reclamo", motivo, motivosReclamo.map { it to it }) { motivo = it }
            if(motivo != "Entrega no registrada") {
                SelectorProveedor("Entrega de referencia", entregaId, s.entregas.filterNot { it.anulada }.map { it.id to "${fechaProveedor(it.registradoEn)} · ${decimalProveedor(it.litros)} L" }) { entregaId = it }
                s.entregas.firstOrNull { it.id == entregaId }?.let { e -> TarjetaProveedor(color = Color(0xFFE8F4EC)) {
                    TextoProveedor("ENTREGA DE REFERENCIA", 11, ProveedorVerde, true)
                    TextoProveedor(fechaProveedor(e.registradoEn), 15, bold = true)
                    TextoProveedor("${decimalProveedor(e.litros)} L", 22, bold = true)
                    TextoProveedor("${s.usuarios[e.usuarioId] ?: "Acopiador"} · ${e.tachos} tachos", 13, ProveedorGris)
                } }
            }
            OutlinedTextField(litros, { litros = it }, label = { Text("Litros que solicitas") }, placeholder = { Text("0.00") }, singleLine = true, keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal), modifier = Modifier.fillMaxWidth())
            OutlinedTextField(descripcion, { descripcion = it.take(2000) }, label = { Text("Descripción") }, placeholder = { Text("Describe el problema con detalle; si falta la entrega, indica fecha y hora.") }, minLines = 3, modifier = Modifier.fillMaxWidth())
            OutlinedButton(onClick = adjuntar, enabled = !s.guardando, modifier = Modifier.fillMaxWidth()) { Text(if(evidencia == null) "+ Adjuntar evidencia fotográfica (opcional)" else "✓ Fotografía adjunta · Cambiar") }
            evidencia?.let { FotoAdjuntaProveedor(it); TextButton(onClick = { evidencia = null }) { Text("Quitar fotografía") } }
            errorFoto?.let { TextoProveedor(it, color = ProveedorRojo) }
            s.error?.let { TextoProveedor(it, color = ProveedorRojo) }
            TextoProveedor("Se conservará en tu dispositivo hasta que esté disponible el envío al administrador.", 12, ProveedorGris)
            BotonProveedor(if(s.guardando) "Guardando…" else "Guardar reclamo", !s.guardando && litros.isNotBlank() && motivo.isNotBlank() && descripcion.trim().length >= 10) {
                vm.reclamar(if(motivo == "Entrega no registrada") null else entregaId, litros, motivo, descripcion, evidencia)
            }
        }
    }
}

@Composable fun TrasladoProveedor(s: PortalProveedorState, vm: PortalProveedorViewModel, navegar: (Pantalla) -> Unit) {
    var zona by rememberSaveable { mutableStateOf("") }
    var motivo by rememberSaveable { mutableStateOf("") }
    LaunchedEffect(Unit) { vm.limpiarMensaje() }
    PaginaProveedor("Solicitar traslado", volver = { navegar(Pantalla.ProveedorHome) }) {
        if(s.confirmacion != null) ResultadoSolicitud(s, navegar)
        else {
            TarjetaProveedor(color = Color(0xFFE8F4EC)) { TextoProveedor("ZONA ACTUAL", 11, ProveedorVerde, true); TextoProveedor(s.zona, 18, bold = true) }
            val destinos = s.zonas.filter { it.activo && it.id != s.proveedor?.zonaId }
            if(destinos.isEmpty()) VacioProveedor("Sin zonas de destino", "No hay otra zona activa descargada para solicitar un traslado.")
            else SelectorProveedor("Zona de destino", zona, destinos.map { it.id to it.nombre }) { zona = it }
            OutlinedTextField(motivo, { motivo = it.take(2000) }, label = { Text("Motivo del traslado") }, minLines = 3, modifier = Modifier.fillMaxWidth())
            TextoProveedor("La solicitud no modifica tu zona. El administrador debe recibirla y aprobarla.", 13, ProveedorGris)
            s.error?.let { TextoProveedor(it, color = ProveedorRojo) }
            BotonProveedor(if(s.guardando) "Guardando…" else "Guardar solicitud", !s.guardando && zona.isNotEmpty() && motivo.trim().length >= 10) { vm.trasladar(zona, motivo) }
        }
    }
}

@Composable fun SolicitudesProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    var mensaje by remember { mutableStateOf<String?>(null) }
    val compartir = recordarCompartirProveedor { mensaje = it }
    PaginaProveedor("Mis solicitudes", "Reclamos y traslados", { navegar(Pantalla.ProveedorHome) }) {
        mensaje?.let { TextoProveedor(it, color = ProveedorRojo) }
        if(s.solicitudes.isEmpty()) VacioProveedor("No tienes solicitudes", "Los reclamos y traslados que guardes aparecerán aquí.")
        s.solicitudes.forEach { solicitud -> TarjetaProveedor {
            TextoProveedor(if(solicitud.tipo == "TRASLADO") "Traslado de zona" else "Reclamo de entrega", 16, bold = true)
            TextoProveedor(fechaProveedor(solicitud.creadaEn), 12, ProveedorGris)
            EtiquetaProveedor(
                when (solicitud.estado) {
                    EstadoSolicitud.APROBADA -> "Aprobada"
                    EstadoSolicitud.ATENDIDA -> "Atendida"
                    EstadoSolicitud.RECHAZADA -> "Rechazada"
                    else -> "Pendiente de revisión"
                },
                solicitud.estado != EstadoSolicitud.APROBADA && solicitud.estado != EstadoSolicitud.ATENDIDA,
            )
            TextoProveedor(solicitud.motivo, bold = true)
            TextoProveedor(solicitud.descripcion)
            solicitud.litros?.let { TextoProveedor("Litros solicitados: ${decimalProveedor(it)} L") }
            if(solicitud.tipo == "TRASLADO") TextoProveedor("Destino: ${s.zonas.firstOrNull { it.id == solicitud.referenciaId }?.nombre ?: solicitud.referenciaId}")
            if(solicitud.evidencia != null) {
                var verFoto by remember(solicitud.id) { mutableStateOf(false) }
                TextButton(onClick = { verFoto = !verFoto }) { Text(if(verFoto) "Ocultar fotografía" else "Ver fotografía adjunta") }
                if(verFoto) FotoAdjuntaProveedor(solicitud.evidencia)
            }
            OutlinedButton(onClick = {
                compartir("Ecolactea Digital — Solicitud ${solicitud.id}\nProveedor: ${s.proveedor!!.nombres} (${s.proveedor.codigo})\nTipo: ${solicitud.tipo}\nReferencia: ${solicitud.referenciaId ?: "Entrega no registrada"}\nMotivo: ${solicitud.motivo}\nLitros: ${solicitud.litros ?: "No aplica"}\n${solicitud.descripcion}\n${fechaProveedor(solicitud.creadaEn)}\nPendiente de recepción y revisión administrativa. La fotografía adjunta permanece en el dispositivo.")
            }, modifier = Modifier.fillMaxWidth()) { Text("Compartir resumen") }
        } }
    }
}

@Composable fun QrProveedor(s: PortalProveedorState, navegar: (Pantalla) -> Unit) {
    var brillo by remember { mutableStateOf(false) }
    var compartiendo by remember { mutableStateOf(false) }
    var mensaje by remember { mutableStateOf<String?>(null) }
    val layer = rememberGraphicsLayer()
    val scope = rememberCoroutineScope()
    BrilloProveedor(brillo)
    PaginaProveedor("Mi código QR", volver = { navegar(Pantalla.ProveedorHome) }) {
        Column(Modifier.fillMaxWidth().padding(vertical = 16.dp), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(24.dp)) {
            Column(horizontalAlignment = Alignment.CenterHorizontally) {
                TextoProveedor(s.proveedor!!.nombres, 18, bold = true)
                TextoProveedor("${s.proveedor.codigo} · Zona ${s.zona}", 14, ProveedorGris)
            }
            Surface(shape = RoundedCornerShape(20.dp), color = Color.White, shadowElevation = 4.dp, modifier = Modifier.drawWithContent { layer.record { this@drawWithContent.drawContent() }; drawContent() }) {
                Image(rememberQrKitPainter(data = generarQrProveedor(s.proveedor!!.id)), "Código QR de identificación del proveedor", Modifier.padding(28.dp).size(200.dp))
            }
            TarjetaProveedor(color = Color(0xFFE8F4EC)) {
                TextoProveedor("Muestra este código al acopiador", 14, ProveedorVerde, true)
                TextoProveedor("El escáner identificará tu cuenta automáticamente", 12, ProveedorGris)
            }
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                Button(onClick = { brillo = !brillo }, modifier = Modifier.weight(1f), shape = RoundedCornerShape(14.dp)) { Text(if(brillo) "Restaurar brillo" else "☀ Aumentar brillo") }
                OutlinedButton(onClick = {
                    scope.launch {
                        compartiendo = true
                        try { shareQrCodeImage(layer.toImageBitmap(), "qr-${s.proveedor!!.codigo}") }
                        catch(e: kotlinx.coroutines.CancellationException) { throw e }
                        catch(e: Exception) { mensaje = "No se pudo compartir el QR: ${e.message}" }
                        finally { compartiendo = false }
                    }
                }, enabled = !compartiendo, modifier = Modifier.weight(1f), shape = RoundedCornerShape(14.dp)) { Text(if(compartiendo) "Compartiendo…" else "Compartir") }
            }
            TarjetaProveedor(color = Color(0xFFFFF8E7)) { TextoProveedor("No compartas públicamente este código. Solo identifica tu cuenta, no autoriza operaciones.", 13, Color(0xFF9C720A)) }
            mensaje?.let { TextoProveedor(it, color = ProveedorRojo) }
        }
    }
}
