package pe.ecolecta.presentation.design

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.spring
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Pestaña de la barra inferior. [insignia] > 0 muestra el contador coral de la web. */
data class ItemNavegacion(val etiqueta: String, val icono: ImageVector, val insignia: Int = 0)

private val TintaNav = Color(0xFF142820)
private val TintaNavOscura = Color(0xFF0A1410)

/**
 * Barra inferior con el lenguaje del menú lateral de la web: fondo tinta, ítems en blanco al 58 %
 * y el activo resaltado con una pastilla blanca (ícono en tinta) que crece con un resorte suave.
 * La comparten Administrador, Acopiador, Proveedor y Técnico de calidad.
 */
@Composable
fun BarraNavegacionInferior(
    items: List<ItemNavegacion>,
    seleccionado: Int,
    onSeleccionar: (Int) -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
) {
    val fondo = if (LocalPaletaEcolecta.current == PaletaOscura) TintaNavOscura else TintaNav
    Column(
        modifier
            .fillMaxWidth()
            .shadow(18.dp, RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp), clip = false, ambientColor = TintaNav, spotColor = TintaNav)
            .clip(RoundedCornerShape(topStart = 24.dp, topEnd = 24.dp))
            .background(fondo)
            .navigationBarsPadding(),
    ) {
        Row(Modifier.fillMaxWidth().height(72.dp).padding(horizontal = 6.dp)) {
            items.forEachIndexed { indice, item ->
                ItemBarra(
                    item = item,
                    activo = indice == seleccionado,
                    habilitado = habilitado,
                    onClick = { onSeleccionar(indice) },
                    modifier = Modifier.weight(1f),
                )
            }
        }
    }
}

@Composable
private fun ItemBarra(item: ItemNavegacion, activo: Boolean, habilitado: Boolean, onClick: () -> Unit, modifier: Modifier) {
    val curva = tween<Color>(300, easing = CurvaLumen)
    val fondoPastilla by animateColorAsState(if (activo) Color.White else Color.Transparent, curva, label = "navFondo")
    val colorIcono by animateColorAsState(if (activo) TintaNav else Color.White.copy(alpha = 0.58f), curva, label = "navIcono")
    val colorTexto by animateColorAsState(if (activo) Color.White else Color.White.copy(alpha = 0.58f), curva, label = "navTexto")
    val anchoPastilla by animateDpAsState(
        if (activo) 52.dp else 36.dp,
        spring(dampingRatio = Spring.DampingRatioMediumBouncy, stiffness = Spring.StiffnessMediumLow),
        label = "navAncho",
    )
    val interaccion = remember { MutableInteractionSource() }
    val escala by animateFloatAsState(if (activo) 1f else 0.94f, tween(300, easing = CurvaLumen), label = "navEscala")

    Column(
        modifier
            .fillMaxHeight()
            .efectoPresion(interaccion, 0.9f)
            .clickable(interactionSource = interaccion, indication = null, enabled = habilitado, role = Role.Tab, onClick = onClick),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(4.dp, Alignment.CenterVertically),
    ) {
        Box(contentAlignment = Alignment.Center) {
            Box(
                Modifier.width(anchoPastilla).height(32.dp).clip(RoundedCornerShape(12.dp)).background(fondoPastilla),
                contentAlignment = Alignment.Center,
            ) {
                Icon(item.icono, contentDescription = item.etiqueta, tint = colorIcono, modifier = Modifier.size(20.dp).scale(escala))
            }
            if (item.insignia > 0) {
                Text(
                    if (item.insignia > 99) "99+" else item.insignia.toString(),
                    modifier = Modifier
                        .align(Alignment.TopEnd)
                        .offset(x = 8.dp, y = (-6).dp)
                        .clip(RoundedCornerShape(10.dp))
                        .background(Color(0xFFEF765D))
                        .widthIn(min = 16.dp)
                        .padding(horizontal = 4.dp, vertical = 1.dp)
                        .aparicionPop(),
                    color = Color.White,
                    fontSize = 9.sp,
                    fontWeight = FontWeight.Bold,
                    textAlign = TextAlign.Center,
                )
            }
        }
        Text(
            item.etiqueta,
            color = colorTexto,
            fontSize = 10.sp,
            fontWeight = if (activo) FontWeight.SemiBold else FontWeight.Medium,
            maxLines = 1,
            overflow = TextOverflow.Ellipsis,
        )
    }
}
