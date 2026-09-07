package pe.ecolecta.presentation.design

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Rect
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.drawscope.rotate
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp

/**
 * Marca de Ecolecta: gota de leche + hoja, en un círculo de marca. Mismo trazo que el ícono del
 * launcher (ver res/drawable-v24/ic_launcher_foreground.xml), reutilizado en Splash y Login para
 * mantener una sola identidad visual.
 */
@Composable
fun EcolectaLogo(
    modifier: Modifier = Modifier,
    tamano: Dp = 96.dp,
    colorFondo: Color? = null,
    colorSimbolo: Color? = null,
    conFondo: Boolean = true,
) {
    val fondo = colorFondo ?: Colores.brand
    val simbolo = colorSimbolo ?: Colores.onBrand

    Box(
        modifier = if (conFondo) modifier.size(tamano).clip(CircleShape).background(color = fondo) else modifier.size(tamano),
        contentAlignment = Alignment.Center,
    ) {
        Canvas(Modifier.size(tamano * 0.62f)) {
            val w = size.width
            val h = size.height
            val cx = w * 0.5f
            val topY = h * 0.20f
            val circleCy = h * 0.60f
            val r = w * 0.36f

            val gota = Path().apply {
                moveTo(cx, topY)
                cubicTo(
                    cx + r * 0.62f, topY + (circleCy - topY) * 0.55f,
                    cx + r * 1.02f, circleCy - r * 0.52f,
                    cx + r, circleCy,
                )
                arcTo(Rect(cx - r, circleCy - r, cx + r, circleCy + r), startAngleDegrees = 0f, sweepAngleDegrees = 180f, forceMoveTo = false)
                cubicTo(
                    cx - r * 1.02f, circleCy - r * 0.52f,
                    cx - r * 0.62f, topY + (circleCy - topY) * 0.55f,
                    cx, topY,
                )
                close()
            }
            drawPath(gota, color = simbolo)

            // Hoja: pequeño óvalo apuntado, apoyado en la punta de la gota, con una nervadura central.
            val hojaAncho = w * 0.40f
            val hojaAlto = h * 0.20f
            val hojaCx = cx + w * 0.06f
            val hojaCy = topY - h * 0.02f
            rotate(degrees = -34f, pivot = Offset(hojaCx, hojaCy)) {
                val hoja = Path().apply {
                    moveTo(hojaCx - hojaAncho / 2f, hojaCy)
                    cubicTo(
                        hojaCx - hojaAncho * 0.15f, hojaCy - hojaAlto / 2f,
                        hojaCx + hojaAncho * 0.15f, hojaCy - hojaAlto / 2f,
                        hojaCx + hojaAncho / 2f, hojaCy,
                    )
                    cubicTo(
                        hojaCx + hojaAncho * 0.15f, hojaCy + hojaAlto / 2f,
                        hojaCx - hojaAncho * 0.15f, hojaCy + hojaAlto / 2f,
                        hojaCx - hojaAncho / 2f, hojaCy,
                    )
                    close()
                }
                drawPath(hoja, color = simbolo)
                drawLine(
                    color = fondo,
                    start = Offset(hojaCx - hojaAncho * 0.38f, hojaCy),
                    end = Offset(hojaCx + hojaAncho * 0.38f, hojaCy),
                    strokeWidth = hojaAlto * 0.09f,
                )
            }
        }
    }
}
