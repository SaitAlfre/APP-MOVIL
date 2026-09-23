package pe.ecolecta.presentation.admin.design

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.IntrinsicSize
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowLeft
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.datetime.LocalDate

/**
 * Tokens del Administrador según el prototipo "Ecolactea Digital" de Figma Make. Como en el
 * portal del proveedor, se fijan en claro: el verde oscuro distingue al rol Administrador.
 */
object AdminColor {
    val verdeOscuro = Color(0xFF1E5631)
    val verde = Color(0xFF2E7D46)
    val verdeSuave = Color(0xFFE8F4EC)
    val crema = Color(0xFFF7F6F1)
    val texto = Color(0xFF25342C)
    val gris = Color(0xFF66736C)
    val borde = Color(0xFFDDE4DF)
    val blanco = Color.White
    val rojo = Color(0xFFC94A4A)
    val rojoSuave = Color(0xFFFDEAEA)
    val ambar = Color(0xFFE8B339)
    val ambarTexto = Color(0xFFB8860B)
    val ambarSuave = Color(0xFFFFF8E7)
    val ambarPildora = Color(0xFFFFF3D4)
    val azul = Color(0xFF3783B5)
    val azulSuave = Color(0xFFE8F0FB)
    val morado = Color(0xFF6B48C8)
    val moradoSuave = Color(0xFFEDE7F9)
    val grisSuave = Color(0xFFF0F0F0)
}

@Composable
fun AdminTexto(
    texto: String,
    size: Int = 14,
    color: Color = AdminColor.texto,
    peso: FontWeight = FontWeight.Normal,
    modifier: Modifier = Modifier,
    maxLineas: Int = Int.MAX_VALUE,
) {
    Text(
        texto,
        modifier = modifier,
        color = color,
        fontSize = size.sp,
        fontWeight = peso,
        lineHeight = (size * 1.4f).sp,
        maxLines = maxLineas,
        overflow = TextOverflow.Ellipsis,
    )
}

/** Barra superior blanca con retroceso opcional (TopAppBar del prototipo). */
@Composable
fun AdminTopBar(
    titulo: String,
    subtitulo: String? = null,
    alVolver: (() -> Unit)? = null,
    accion: (@Composable RowScope.() -> Unit)? = null,
) {
    Column(Modifier.fillMaxWidth().background(AdminColor.blanco)) {
        Row(
            Modifier.fillMaxWidth().heightIn(min = 60.dp).padding(horizontal = if (alVolver != null) 4.dp else 16.dp, vertical = 8.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            if (alVolver != null) {
                IconButton(onClick = alVolver) {
                    Icon(Icons.AutoMirrored.Filled.KeyboardArrowLeft, contentDescription = "Volver", tint = AdminColor.gris, modifier = Modifier.size(30.dp))
                }
            }
            Column(Modifier.weight(1f)) {
                AdminTexto(titulo, 17, peso = FontWeight.Bold, maxLineas = 1)
                subtitulo?.let { AdminTexto(it, 12, AdminColor.gris, maxLineas = 1) }
            }
            accion?.invoke(this)
            if (alVolver != null || accion != null) Spacer(Modifier.width(8.dp))
        }
        Box(Modifier.fillMaxWidth().height(1.dp).background(AdminColor.borde))
    }
}

/** Tarjeta blanca con radio 16 y sombra tenue; [acento] dibuja el borde izquierdo de las alertas. */
@Composable
fun AdminCard(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    color: Color = AdminColor.blanco,
    radio: Int = 16,
    acento: Color? = null,
    padding: Int = 16,
    contenido: @Composable ColumnScope.() -> Unit,
) {
    val forma = RoundedCornerShape(radio.dp)
    Surface(
        modifier = modifier.fillMaxWidth().clip(forma).let { if (onClick != null) it.clickable(onClick = onClick) else it },
        shape = forma,
        color = color,
        shadowElevation = if (color == AdminColor.blanco) 1.dp else 0.dp,
    ) {
        Row(Modifier.height(IntrinsicSize.Min)) {
            if (acento != null) Box(Modifier.width(3.dp).fillMaxHeight().background(acento))
            Column(Modifier.weight(1f).padding(padding.dp), content = contenido)
        }
    }
}

@Composable
fun AdminSeccion(titulo: String, modifier: Modifier = Modifier) {
    Text(
        titulo.uppercase(),
        modifier = modifier.padding(top = 4.dp, bottom = 10.dp),
        color = AdminColor.gris,
        fontSize = 13.sp,
        fontWeight = FontWeight.Bold,
        letterSpacing = 0.5.sp,
    )
}

@Composable
fun AdminEtiqueta(texto: String, color: Color, fondo: Color, modifier: Modifier = Modifier, radio: Int = 8) {
    Box(modifier.clip(RoundedCornerShape(radio.dp)).background(fondo).padding(horizontal = 10.dp, vertical = 4.dp)) {
        AdminTexto(texto, 11, color, FontWeight.Bold, maxLineas = 1)
    }
}

/** SyncBadge del prototipo: ámbar con pendientes, verde cuando todo está guardado. */
@Composable
fun AdminPildoraSync(pendientes: Int, modifier: Modifier = Modifier) {
    val (texto, color, fondo) = if (pendientes > 0) {
        Triple("$pendientes pendiente${if (pendientes == 1) "" else "s"}", AdminColor.ambarTexto, AdminColor.ambarPildora)
    } else {
        Triple("Al día", AdminColor.verde, AdminColor.verdeSuave)
    }
    Row(
        modifier.clip(RoundedCornerShape(20.dp)).background(fondo).padding(horizontal = 10.dp, vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(4.dp),
    ) {
        Box(Modifier.size(6.dp).clip(CircleShape).background(if (pendientes > 0) AdminColor.ambar else AdminColor.verde))
        AdminTexto(texto, 12, color, FontWeight.SemiBold, maxLineas = 1)
    }
}

@Composable
fun AdminBoton(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    color: Color = AdminColor.verdeOscuro,
    radio: Int = 14,
    alto: Int = 48,
) {
    Box(
        modifier.fillMaxWidth().heightIn(min = alto.dp).clip(RoundedCornerShape(radio.dp))
            .background(if (habilitado) color else color.copy(alpha = 0.4f))
            .clickable(enabled = habilitado, onClick = onClick)
            .padding(horizontal = 14.dp, vertical = 10.dp),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 14, AdminColor.blanco, FontWeight.Bold, maxLineas = 1) }
}

@Composable
fun AdminBotonBorde(texto: String, color: Color, onClick: () -> Unit, modifier: Modifier = Modifier) {
    Box(
        modifier.fillMaxWidth().heightIn(min = 50.dp).clip(RoundedCornerShape(14.dp)).background(AdminColor.blanco)
            .border(1.5.dp, color, RoundedCornerShape(14.dp)).clickable(onClick = onClick).padding(14.dp),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 15, color, FontWeight.Bold) }
}

/** Botón compacto de las tarjetas (Aprobar, Revisar, Descartar…). */
@Composable
fun AdminBotonChico(texto: String, color: Color, fondo: Color, onClick: () -> Unit, habilitado: Boolean = true) {
    Box(
        Modifier.clip(RoundedCornerShape(8.dp)).background(if (habilitado) fondo else fondo.copy(alpha = 0.5f))
            .clickable(enabled = habilitado, onClick = onClick).padding(horizontal = 14.dp, vertical = 8.dp),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 12, color, FontWeight.Bold, maxLineas = 1) }
}

/** Bloque gris de cifras centradas (Litros / Proveedores / Precio) dentro de una tarjeta. */
@Composable
fun AdminCifras(cifras: List<Pair<String, String>>, modifier: Modifier = Modifier, tamanoValor: Int = 18) {
    Row(
        modifier.fillMaxWidth().clip(RoundedCornerShape(10.dp)).background(AdminColor.crema).padding(horizontal = 12.dp, vertical = 10.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        cifras.forEach { (etiqueta, valor) ->
            Column(Modifier.weight(1f), horizontalAlignment = Alignment.CenterHorizontally) {
                AdminTexto(valor, tamanoValor, peso = FontWeight.ExtraBold, maxLineas = 1)
                AdminTexto(etiqueta, 10, AdminColor.gris, maxLineas = 1)
            }
        }
    }
}

@Composable
fun AdminVacio(titulo: String, detalle: String, modifier: Modifier = Modifier) {
    AdminCard(modifier) {
        AdminTexto(titulo, 15, peso = FontWeight.Bold)
        Spacer(Modifier.height(4.dp))
        AdminTexto(detalle, 13, AdminColor.gris)
    }
}

@Composable
fun AdminCargando(modifier: Modifier = Modifier) {
    Box(modifier.fillMaxWidth().padding(40.dp), contentAlignment = Alignment.Center) {
        CircularProgressIndicator(color = AdminColor.verde)
    }
}

@Composable
fun AdminMensaje(texto: String, error: Boolean, alCerrar: () -> Unit, modifier: Modifier = Modifier) {
    AdminCard(modifier, color = if (error) AdminColor.rojoSuave else AdminColor.verdeSuave, onClick = alCerrar) {
        AdminTexto(texto, 13, if (error) AdminColor.rojo else AdminColor.verde, FontWeight.SemiBold)
    }
}

private val DIAS = listOf("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")
private val MESES = listOf("enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre")
private val MESES_CORTOS = listOf("Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic")

/** "Martes, 22 de septiembre". */
fun fechaLarga(fecha: LocalDate): String = "${DIAS[fecha.dayOfWeek.ordinal]}, ${fecha.day} de ${MESES[fecha.month.ordinal]}"

/** "Sem 18–24 Sep" o "Sem 28 Ago–3 Sep", como en Reportes del prototipo. */
fun rangoSemana(desde: LocalDate, hasta: LocalDate): String =
    if (desde.month == hasta.month) "Sem ${desde.day}–${hasta.day} ${MESES_CORTOS[hasta.month.ordinal]}"
    else "Sem ${desde.day} ${MESES_CORTOS[desde.month.ordinal]}–${hasta.day} ${MESES_CORTOS[hasta.month.ordinal]}"

/** "Hace 15m", "Hace 2h", "Hace 3d". */
fun haceTiempo(epochMs: Long, ahoraMs: Long): String {
    val minutos = ((ahoraMs - epochMs) / 60_000).coerceAtLeast(0)
    return when {
        minutos < 1 -> "Ahora"
        minutos < 60 -> "Hace ${minutos}m"
        minutos < 60 * 24 -> "Hace ${minutos / 60}h"
        else -> "Hace ${minutos / (60 * 24)}d"
    }
}

/** Separador de miles con coma y sin decimales innecesarios: 1842.0 → "1,842"; 202.5 → "202.5". */
fun cifra(valor: Double): String {
    val centesimos = kotlin.math.round(valor * 100).toLong()
    val entero = (centesimos / 100).toString().reversed().chunked(3).joinToString(",").reversed()
    val decimales = (centesimos % 100).toInt()
    return when {
        decimales == 0 -> entero
        decimales % 10 == 0 -> "$entero.${decimales / 10}"
        else -> "$entero.${decimales.toString().padStart(2, '0')}"
    }
}

fun soles(valor: Double): String {
    val centesimos = kotlin.math.round(valor * 100).toLong()
    val entero = (centesimos / 100).toString().reversed().chunked(3).joinToString(",").reversed()
    return "S/ $entero.${(centesimos % 100).toString().padStart(2, '0')}"
}
