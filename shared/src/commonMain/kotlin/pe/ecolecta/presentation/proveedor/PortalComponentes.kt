package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.datetime.*
import kotlin.math.roundToLong
import kotlin.time.Instant

val ProveedorAzul = Color(0xFF3783B5)
val ProveedorFondo = Color(0xFFF7F6F1)
val ProveedorTexto = Color(0xFF25342C)
val ProveedorGris = Color(0xFF66736C)
val ProveedorVerde = Color(0xFF2E7D46)
val ProveedorRojo = Color(0xFFC94A4A)

fun decimalProveedor(valor: Double): String {
    val cents = (valor * 100).roundToLong()
    return "${cents / 100}.${kotlin.math.abs(cents % 100).toString().padStart(2, '0')}"
}
fun fechaProveedor(epoch: Long): String {
    val d = Instant.fromEpochMilliseconds(epoch).toLocalDateTime(TimeZone.of("America/Lima"))
    return "${d.day}/${d.month.ordinal + 1}/${d.year} · ${d.hour.toString().padStart(2,'0')}:${d.minute.toString().padStart(2,'0')}"
}

@Composable fun TextoProveedor(texto: String, size: Int = 14, color: Color = ProveedorTexto, bold: Boolean = false, modifier: Modifier = Modifier) {
    Text(texto, modifier, color, fontSize = size.sp, fontWeight = if (bold) FontWeight.Bold else FontWeight.Normal, lineHeight = (size + 5).sp)
}
@Composable fun TarjetaProveedor(modifier: Modifier = Modifier, color: Color = Color.White, accion: (() -> Unit)? = null, content: @Composable ColumnScope.() -> Unit) {
    if (accion != null) Surface(onClick = accion, modifier = modifier.fillMaxWidth(), color = color, shape = RoundedCornerShape(14.dp), shadowElevation = 1.dp) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp), content = content)
    } else Surface(modifier = modifier.fillMaxWidth(), color = color, shape = RoundedCornerShape(14.dp), shadowElevation = 1.dp) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp), content = content)
    }
}
@Composable fun CabeceraProveedor(titulo: String, subtitulo: String? = null, volver: (() -> Unit)? = null) {
    Surface(color = Color.White) {
        Row(Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 12.dp), verticalAlignment = Alignment.CenterVertically) {
            if (volver != null) IconButton(onClick = volver) { Icon(Icons.AutoMirrored.Filled.ArrowBack, "Volver", tint = ProveedorTexto) }
            Column { TextoProveedor(titulo, 20, bold = true); subtitulo?.let { TextoProveedor(it, 12, ProveedorGris) } }
        }
    }
}
@Composable fun PaginaProveedor(titulo: String, subtitulo: String? = null, volver: (() -> Unit)? = null, content: @Composable ColumnScope.() -> Unit) {
    Column(Modifier.fillMaxSize()) {
        CabeceraProveedor(titulo, subtitulo, volver)
        Column(Modifier.fillMaxSize().imePadding().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp), content = content)
    }
}
@Composable fun EtiquetaProveedor(texto: String, advertencia: Boolean = false) {
    Surface(color = if(advertencia) Color(0xFFFFF8E7) else Color(0xFFE8F4EC), shape = RoundedCornerShape(6.dp)) {
        TextoProveedor(texto, 11, if(advertencia) Color(0xFF9C720A) else ProveedorVerde, true, Modifier.padding(horizontal = 8.dp, vertical = 4.dp))
    }
}
@Composable fun BotonProveedor(texto: String, habilitado: Boolean = true, accion: () -> Unit) {
    Button(onClick = accion, enabled = habilitado, modifier = Modifier.fillMaxWidth().heightIn(min = 48.dp), shape = RoundedCornerShape(14.dp), colors = ButtonDefaults.buttonColors(containerColor = ProveedorVerde)) { Text(texto) }
}
@Composable fun VacioProveedor(titulo: String, detalle: String) { TarjetaProveedor { TextoProveedor(titulo, 16, bold = true); TextoProveedor(detalle, 14, ProveedorGris) } }
@Composable fun CargandoProveedor() { Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = ProveedorAzul) } }
@Composable fun ErrorProveedor(mensaje: String, reintentar: () -> Unit) { Column(Modifier.padding(24.dp)) { TextoProveedor(mensaje, color = ProveedorRojo); BotonProveedor("Reintentar", accion = reintentar) } }
