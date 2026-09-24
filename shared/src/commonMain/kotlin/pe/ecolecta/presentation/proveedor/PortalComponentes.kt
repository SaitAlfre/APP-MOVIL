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
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.runtime.remember
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.unit.em
import pe.ecolecta.presentation.design.Antetitulo
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonIconoBorde
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.aparicionEscalonada
import pe.ecolecta.presentation.design.efectoPresion

// Tokens del portal alineados con el panel web (`--eh-*`): tinta, crema, salvia y tonos de insignia.
val ProveedorAzul = Color(0xFF285989)
val ProveedorFondo = Color(0xFFF4F4EF)
val ProveedorTexto = Color(0xFF17231E)
val ProveedorGris = Color(0xFF66706B)
val ProveedorVerde = Color(0xFF39765D)
val ProveedorRojo = Color(0xFFB55E4D)
val ProveedorTinta = Color(0xFF142820)
val ProveedorSalvia = Color(0xFF6C8C7B)
private val ProveedorBorde = Color(0x1A17231E)

fun decimalProveedor(valor: Double): String {
    val cents = (valor * 100).roundToLong()
    return "${cents / 100}.${kotlin.math.abs(cents % 100).toString().padStart(2, '0')}"
}
fun fechaProveedor(epoch: Long): String {
    val d = Instant.fromEpochMilliseconds(epoch).toLocalDateTime(TimeZone.of("America/Lima"))
    return "${d.day}/${d.month.ordinal + 1}/${d.year} · ${d.hour.toString().padStart(2,'0')}:${d.minute.toString().padStart(2,'0')}"
}

@Composable fun TextoProveedor(texto: String, size: Int = 14, color: Color = ProveedorTexto, bold: Boolean = false, modifier: Modifier = Modifier) {
    Text(
        texto, modifier, color, fontSize = size.sp,
        fontWeight = if (bold) FontWeight.SemiBold else FontWeight.Normal,
        lineHeight = (size + 5).sp,
        letterSpacing = if (size >= 18) (-0.03).em else 0.em,
    )
}

/** Tarjeta `x-ui.card` de la web: blanca, borde tenue, radio 20, entrada escalonada y presión al tocar. */
@Composable fun TarjetaProveedor(modifier: Modifier = Modifier, color: Color = Color.White, accion: (() -> Unit)? = null, content: @Composable ColumnScope.() -> Unit) {
    val forma = RoundedCornerShape(20.dp)
    val borde = if (color == Color.White) BorderStroke(1.dp, ProveedorBorde) else null
    val interaccion = remember { MutableInteractionSource() }
    if (accion != null) Surface(
        onClick = accion, modifier = modifier.fillMaxWidth().aparicionEscalonada().efectoPresion(interaccion, 0.985f),
        color = color, shape = forma, border = borde, interactionSource = interaccion,
    ) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp), content = content)
    } else Surface(modifier = modifier.fillMaxWidth().aparicionEscalonada(), color = color, shape = forma, border = borde) {
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(4.dp), content = content)
    }
}

/** Cabecera como el header de la web: crema, antetítulo salvia, título con tracking negativo y línea inferior. */
@Composable fun CabeceraProveedor(titulo: String, subtitulo: String? = null, volver: (() -> Unit)? = null) {
    Column(Modifier.fillMaxWidth().background(ProveedorFondo)) {
        Row(Modifier.fillMaxWidth().heightIn(min = 64.dp).padding(horizontal = 16.dp, vertical = 12.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            if (volver != null) BotonIconoBorde(Icons.AutoMirrored.Filled.ArrowBack, "Volver", volver)
            Column {
                subtitulo?.let { Antetitulo(it, color = ProveedorSalvia) }
                Text(titulo, color = ProveedorTexto, fontSize = 22.sp, fontWeight = FontWeight.SemiBold, letterSpacing = (-0.04).em, lineHeight = 28.sp)
            }
        }
        Box(Modifier.fillMaxWidth().height(1.dp).background(ProveedorBorde))
    }
}
@Composable fun PaginaProveedor(titulo: String, subtitulo: String? = null, volver: (() -> Unit)? = null, content: @Composable ColumnScope.() -> Unit) {
    Column(Modifier.fillMaxSize()) {
        CabeceraProveedor(titulo, subtitulo, volver)
        Column(Modifier.fillMaxSize().imePadding().verticalScroll(rememberScrollState()).padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp), content = content)
    }
}

/** Insignia `x-ui.badge` de la web: pastilla con punto. */
@Composable fun EtiquetaProveedor(texto: String, advertencia: Boolean = false) {
    val color = if (advertencia) Color(0xFF926B22) else ProveedorVerde
    Row(
        Modifier.clip(RoundedCornerShape(50)).background(if (advertencia) Color(0xFFFFF0D1) else Color(0xFFE3F1E8)).padding(horizontal = 10.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Box(Modifier.size(5.dp).clip(CircleShape).background(color))
        Text(texto, color = color, fontSize = 11.sp, fontWeight = FontWeight.SemiBold)
    }
}

/** Botón principal de la web: tinta con sombra tinta y hundimiento al presionar. */
@Composable fun BotonProveedor(texto: String, habilitado: Boolean = true, accion: () -> Unit) {
    val forma = RoundedCornerShape(12.dp)
    val interaccion = remember { MutableInteractionSource() }
    Button(
        onClick = accion, enabled = habilitado, interactionSource = interaccion, shape = forma,
        modifier = Modifier.fillMaxWidth().heightIn(min = 50.dp).efectoPresion(interaccion)
            .then(if (habilitado) Modifier.shadow(10.dp, forma, clip = false, ambientColor = ProveedorTinta.copy(alpha = 0.35f), spotColor = ProveedorTinta.copy(alpha = 0.45f)) else Modifier),
        colors = ButtonDefaults.buttonColors(containerColor = ProveedorTinta, contentColor = Color.White, disabledContainerColor = ProveedorTinta.copy(alpha = 0.35f), disabledContentColor = Color.White.copy(alpha = 0.8f)),
    ) { Text(texto, fontSize = 14.sp, fontWeight = FontWeight.SemiBold) }
}
@Composable fun VacioProveedor(titulo: String, detalle: String) { TarjetaProveedor { TextoProveedor(titulo, 15, bold = true); TextoProveedor(detalle, 13, ProveedorGris) } }
@Composable fun CargandoProveedor() { Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) { CircularProgressIndicator(color = ProveedorSalvia, trackColor = Color(0xFFEEF0E9), strokeWidth = 3.dp, modifier = Modifier.size(36.dp)) } }
@Composable fun ErrorProveedor(mensaje: String, reintentar: () -> Unit) { Column(Modifier.padding(24.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) { Banner(mensaje, TipoBanner.ERROR); BotonProveedor("Reintentar", accion = reintentar) } }
