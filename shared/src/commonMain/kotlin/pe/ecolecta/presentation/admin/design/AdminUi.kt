package pe.ecolecta.presentation.admin.design

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.RowScope
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
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
import androidx.compose.ui.draw.drawBehind
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.datetime.LocalDate
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.ErrorOutline
import androidx.compose.material.icons.outlined.Info
import androidx.compose.material3.ripple
import androidx.compose.runtime.remember
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.em
import pe.ecolecta.presentation.design.aparicionEscalonada
import pe.ecolecta.presentation.design.aparicionPop
import pe.ecolecta.presentation.design.efectoPresion

/**
 * Tokens del Administrador, alineados con los `--eh-*` del panel web (diseño "Lumen"): tinta
 * #142820 para acciones y cabeceras, fondo crema #f4f4ef, lima como acento y los tonos suaves de
 * las insignias. Se fijan en claro, igual que el panel.
 */
object AdminColor {
    val verdeOscuro = Color(0xFF142820)
    val verde = Color(0xFF39765D)
    val verdeSuave = Color(0xFFE3F1E8)
    val crema = Color(0xFFF4F4EF)
    val texto = Color(0xFF17231E)
    val gris = Color(0xFF66706B)
    val borde = Color(0x1A17231E)
    val blanco = Color.White
    val rojo = Color(0xFFB55E4D)
    val rojoSuave = Color(0xFFF8E5DF)
    val ambar = Color(0xFFC9A14F)
    val ambarTexto = Color(0xFF926B22)
    val ambarSuave = Color(0xFFFFF0D1)
    val ambarPildora = Color(0xFFFFF0D1)
    val azul = Color(0xFF285989)
    val azulSuave = Color(0xFFD9ECFF)
    val morado = Color(0xFF655497)
    val moradoSuave = Color(0xFFEDE9F8)
    val grisSuave = Color(0xFFEEF0E9)
    val lima = Color(0xFFD8FF57)
    val salvia = Color(0xFF6C8C7B)
    val coral = Color(0xFFEF765D)
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
        // Tracking negativo en cifras y titulares, como `tracking-[-.04em]` de la web.
        letterSpacing = if (size >= 17) (-0.03).em else 0.em,
        maxLines = maxLineas,
        overflow = TextOverflow.Ellipsis,
    )
}

/** Cabecera como el header del panel web: fondo crema, línea inferior tenue y retroceso cuadrado con borde. */
@Composable
fun AdminTopBar(
    titulo: String,
    subtitulo: String? = null,
    alVolver: (() -> Unit)? = null,
    accion: (@Composable RowScope.() -> Unit)? = null,
) {
    Column(Modifier.fillMaxWidth().background(AdminColor.crema)) {
        Row(
            Modifier.fillMaxWidth().heightIn(min = 64.dp).padding(horizontal = 16.dp, vertical = 10.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            if (alVolver != null) {
                val interaccion = remember { MutableInteractionSource() }
                Box(
                    Modifier.size(40.dp).efectoPresion(interaccion, 0.92f).clip(RoundedCornerShape(12.dp))
                        .background(AdminColor.blanco.copy(alpha = 0.65f))
                        .border(1.dp, AdminColor.borde, RoundedCornerShape(12.dp))
                        .clickable(interactionSource = interaccion, indication = null, onClick = alVolver),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(Icons.AutoMirrored.Filled.KeyboardArrowLeft, contentDescription = "Volver", tint = AdminColor.texto, modifier = Modifier.size(24.dp))
                }
            }
            Column(Modifier.weight(1f)) {
                subtitulo?.let {
                    Text(
                        it.uppercase(), color = AdminColor.salvia, fontSize = 10.sp, fontWeight = FontWeight.SemiBold,
                        letterSpacing = 0.16.em, maxLines = 1, overflow = TextOverflow.Ellipsis,
                    )
                }
                AdminTexto(titulo, 19, peso = FontWeight.SemiBold, maxLineas = 1)
            }
            accion?.invoke(this)
        }
        Box(Modifier.fillMaxWidth().height(1.dp).background(AdminColor.borde))
    }
}

/**
 * Tarjeta `x-ui.card` de la web: blanca, borde tenue de 1 dp, sin sombra y radio 20. [acento]
 * dibuja el borde izquierdo de las alertas. Entra escalonada y se hunde al tocarla.
 */
@Composable
fun AdminCard(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    color: Color = AdminColor.blanco,
    radio: Int = 20,
    acento: Color? = null,
    padding: Int = 16,
    contenido: @Composable ColumnScope.() -> Unit,
) {
    val forma = RoundedCornerShape(maxOf(radio, 18).dp)
    val interaccion = remember { MutableInteractionSource() }
    Surface(
        modifier = modifier.fillMaxWidth().aparicionEscalonada()
            .let { if (onClick != null) it.efectoPresion(interaccion, 0.985f) else it }
            .clip(forma)
            .let { if (onClick != null) it.clickable(interactionSource = interaccion, indication = ripple(), onClick = onClick) else it },
        shape = forma,
        color = color,
        border = if (color == AdminColor.blanco) BorderStroke(1.dp, AdminColor.borde) else null,
    ) {
        // El acento se dibuja detrás en vez de medir la altura con IntrinsicSize.Min: la medición
        // intrínseca no admite listas perezosas (LazyRow) dentro de la tarjeta y cerraba la app.
        Column(
            Modifier
                .let { if (acento != null) it.drawBehind { drawRect(acento, size = Size(3.dp.toPx(), size.height)) } else it }
                .padding(start = padding.dp + if (acento != null) 3.dp else 0.dp, top = padding.dp, end = padding.dp, bottom = padding.dp),
            content = contenido,
        )
    }
}

/** Antetítulo de sección de la web: mayúsculas pequeñas en salvia con tracking amplio. */
@Composable
fun AdminSeccion(titulo: String, modifier: Modifier = Modifier) {
    Text(
        titulo.uppercase(),
        modifier = modifier.padding(top = 6.dp, bottom = 10.dp),
        color = AdminColor.salvia,
        fontSize = 11.sp,
        fontWeight = FontWeight.SemiBold,
        letterSpacing = 0.18.em,
    )
}

/** Insignia `x-ui.badge`: pastilla redondeada con punto y texto semibold. */
@Composable
fun AdminEtiqueta(texto: String, color: Color, fondo: Color, modifier: Modifier = Modifier, radio: Int = 50) {
    Row(
        modifier.clip(RoundedCornerShape(50)).background(fondo).padding(horizontal = 10.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Box(Modifier.size(5.dp).clip(CircleShape).background(color))
        AdminTexto(texto, 11, color, FontWeight.SemiBold, maxLineas = 1)
    }
}

/** SyncBadge: ámbar con pendientes, verde cuando todo está guardado. */
@Composable
fun AdminPildoraSync(pendientes: Int, modifier: Modifier = Modifier) {
    val (texto, color, fondo) = if (pendientes > 0) {
        Triple("$pendientes pendiente${if (pendientes == 1) "" else "s"}", AdminColor.ambarTexto, AdminColor.ambarPildora)
    } else {
        Triple("Al día", AdminColor.verde, AdminColor.verdeSuave)
    }
    Row(
        modifier.aparicionPop().clip(RoundedCornerShape(50)).background(fondo).padding(horizontal = 10.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Box(Modifier.size(5.dp).clip(CircleShape).background(if (pendientes > 0) AdminColor.ambarTexto else AdminColor.verde))
        AdminTexto(texto, 11, color, FontWeight.SemiBold, maxLineas = 1)
    }
}

/** Botón principal de la web: tinta, sombra tinta y hundimiento al presionar. */
@Composable
fun AdminBoton(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    color: Color = AdminColor.verdeOscuro,
    radio: Int = 12,
    alto: Int = 48,
) {
    val forma = RoundedCornerShape(minOf(radio, 14).dp)
    val interaccion = remember { MutableInteractionSource() }
    Box(
        modifier.fillMaxWidth().heightIn(min = alto.dp).efectoPresion(interaccion)
            .let { if (habilitado) it.shadow(10.dp, forma, clip = false, ambientColor = color.copy(alpha = 0.35f), spotColor = color.copy(alpha = 0.45f)) else it }
            .clip(forma)
            .background(if (habilitado) color else color.copy(alpha = 0.35f))
            .clickable(interactionSource = interaccion, indication = ripple(), enabled = habilitado, onClick = onClick)
            .padding(horizontal = 14.dp, vertical = 10.dp),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 14, AdminColor.blanco, FontWeight.SemiBold, maxLineas = 1) }
}

@Composable
fun AdminBotonBorde(texto: String, color: Color, onClick: () -> Unit, modifier: Modifier = Modifier) {
    val forma = RoundedCornerShape(12.dp)
    val interaccion = remember { MutableInteractionSource() }
    Box(
        modifier.fillMaxWidth().heightIn(min = 50.dp).efectoPresion(interaccion).clip(forma).background(AdminColor.blanco.copy(alpha = 0.65f))
            .border(1.dp, color.copy(alpha = 0.4f), forma)
            .clickable(interactionSource = interaccion, indication = ripple(), onClick = onClick).padding(14.dp),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 14, color, FontWeight.SemiBold) }
}

/** Botón compacto de las tarjetas (Aprobar, Revisar, Descartar…). */
@Composable
fun AdminBotonChico(texto: String, color: Color, fondo: Color, onClick: () -> Unit, habilitado: Boolean = true) {
    val interaccion = remember { MutableInteractionSource() }
    Box(
        Modifier.efectoPresion(interaccion, 0.94f).clip(RoundedCornerShape(10.dp)).background(if (habilitado) fondo else fondo.copy(alpha = 0.5f))
            .clickable(interactionSource = interaccion, indication = ripple(), enabled = habilitado, onClick = onClick)
            .padding(horizontal = 12.dp, vertical = 8.dp),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 11, color, FontWeight.SemiBold, maxLineas = 1) }
}

/** Bloque de cifras centradas (Litros / Proveedores / Precio) dentro de una tarjeta. */
@Composable
fun AdminCifras(cifras: List<Pair<String, String>>, modifier: Modifier = Modifier, tamanoValor: Int = 18) {
    Row(
        modifier.fillMaxWidth().clip(RoundedCornerShape(14.dp)).background(AdminColor.grisSuave).padding(horizontal = 12.dp, vertical = 12.dp),
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        cifras.forEach { (etiqueta, valor) ->
            Column(Modifier.weight(1f), horizontalAlignment = Alignment.CenterHorizontally) {
                AdminTexto(valor, tamanoValor, peso = FontWeight.SemiBold, maxLineas = 1)
                AdminTexto(etiqueta, 10, AdminColor.gris, FontWeight.Medium, maxLineas = 1)
            }
        }
    }
}

/** Estado vacío `x-ui.empty` de la web. */
@Composable
fun AdminVacio(titulo: String, detalle: String, modifier: Modifier = Modifier) {
    AdminCard(modifier) {
        Column(Modifier.fillMaxWidth().padding(vertical = 10.dp), horizontalAlignment = Alignment.CenterHorizontally) {
            Box(Modifier.size(44.dp).clip(RoundedCornerShape(14.dp)).background(AdminColor.grisSuave), contentAlignment = Alignment.Center) {
                Icon(Icons.Outlined.Info, contentDescription = null, tint = AdminColor.salvia, modifier = Modifier.size(22.dp))
            }
            Spacer(Modifier.height(12.dp))
            AdminTexto(titulo, 14, peso = FontWeight.SemiBold)
            Spacer(Modifier.height(2.dp))
            Text(detalle, color = AdminColor.gris, fontSize = 12.sp, lineHeight = 17.sp, textAlign = TextAlign.Center)
        }
    }
}

@Composable
fun AdminCargando(modifier: Modifier = Modifier) {
    Box(modifier.fillMaxWidth().padding(40.dp), contentAlignment = Alignment.Center) {
        CircularProgressIndicator(color = AdminColor.salvia, trackColor = AdminColor.grisSuave, strokeWidth = 3.dp, modifier = Modifier.size(34.dp))
    }
}

/** Aviso `x-ui.alert` de la web: fondo suave, borde del tono y entrada `pop`. */
@Composable
fun AdminMensaje(texto: String, error: Boolean, alCerrar: () -> Unit, modifier: Modifier = Modifier) {
    val color = if (error) AdminColor.rojo else AdminColor.verde
    val forma = RoundedCornerShape(16.dp)
    Row(
        modifier.fillMaxWidth().aparicionPop().clip(forma).background(if (error) AdminColor.rojoSuave else AdminColor.verdeSuave)
            .border(1.dp, color.copy(alpha = 0.2f), forma).clickable(onClick = alCerrar).padding(horizontal = 14.dp, vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        Box(Modifier.size(24.dp).clip(CircleShape).background(color.copy(alpha = 0.12f)), contentAlignment = Alignment.Center) {
            Icon(if (error) Icons.Outlined.ErrorOutline else Icons.Outlined.CheckCircle, contentDescription = null, tint = color, modifier = Modifier.size(14.dp))
        }
        AdminTexto(texto, 13, color, FontWeight.Medium, modifier = Modifier.weight(1f))
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
