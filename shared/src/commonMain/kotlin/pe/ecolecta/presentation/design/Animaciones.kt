package pe.ecolecta.presentation.design

import androidx.compose.animation.core.Animatable
import androidx.compose.animation.core.CubicBezierEasing
import androidx.compose.animation.core.Spring
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.spring
import androidx.compose.animation.core.tween
import androidx.compose.foundation.interaction.InteractionSource
import androidx.compose.foundation.interaction.collectIsPressedAsState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.Modifier
import androidx.compose.ui.composed
import androidx.compose.ui.graphics.graphicsLayer
import androidx.compose.ui.platform.LocalDensity
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.delay
import kotlin.time.TimeSource

/**
 * Lenguaje de movimiento del panel web (app.css): la misma curva `cubic-bezier(.2,.8,.2,1)` y las
 * mismas animaciones — `rise` (entrada que sube 14 px), stagger de 60–70 ms entre bloques y `pop`
 * para mensajes — trasladadas a Compose para que la app se sienta igual que la web.
 */
val CurvaLumen = CubicBezierEasing(0.2f, 0.8f, 0.2f, 1f)

private const val DURACION_RISE = 600
private const val PASO_STAGGER = 60
private const val TOPE_STAGGER = 420

/** Ventana tras abrir una pantalla en la que los bloques aún entran escalonados (luego, sin animación). */
private const val VENTANA_ENTRADA_MS = 550L

class CoordinadorEntrada internal constructor() {
    internal val inicio = TimeSource.Monotonic.markNow()
    internal var siguiente = 0
}

val LocalCoordinadorEntrada = staticCompositionLocalOf<CoordinadorEntrada?> { null }

/**
 * Marca el inicio de una "página": cada bloque animado que aparezca dentro entra escalonado, como
 * `.admin-page > *` en la web. Cambiar [clave] (otra pantalla, otro paso) reinicia la secuencia.
 */
@Composable
fun EscenarioAnimado(clave: Any?, contenido: @Composable () -> Unit) {
    val coordinador = remember(clave) { CoordinadorEntrada() }
    CompositionLocalProvider(LocalCoordinadorEntrada provides coordinador, content = contenido)
}

/**
 * Entrada `rise` escalonada: sube 14 dp y aparece. Solo anima los bloques que se componen al abrir
 * la pantalla; los que llegan después por scroll aparecen quietos para no distraer.
 */
fun Modifier.aparicionEscalonada(): Modifier = composed {
    val coordinador = LocalCoordinadorEntrada.current
    val retraso = remember {
        coordinador?.takeIf { it.inicio.elapsedNow().inWholeMilliseconds < VENTANA_ENTRADA_MS }
            ?.let { (it.siguiente++ * PASO_STAGGER).coerceAtMost(TOPE_STAGGER) }
    }
    if (retraso == null) return@composed this
    val progreso = remember { Animatable(0f) }
    LaunchedEffect(Unit) {
        delay(retraso.toLong())
        progreso.animateTo(1f, tween(DURACION_RISE, easing = CurvaLumen))
    }
    val desplazamiento = with(LocalDensity.current) { 14.dp.toPx() }
    graphicsLayer {
        alpha = progreso.value
        translationY = (1f - progreso.value) * desplazamiento
    }
}

/** Transición suave al cambiar de pantalla o pestaña: fundido corto con un leve ascenso. */
fun Modifier.entradaPantalla(clave: Any?): Modifier = composed {
    val progreso = remember(clave) { Animatable(0f) }
    LaunchedEffect(clave) { progreso.animateTo(1f, tween(320, easing = CurvaLumen)) }
    val desplazamiento = with(LocalDensity.current) { 8.dp.toPx() }
    graphicsLayer {
        alpha = progreso.value
        translationY = (1f - progreso.value) * desplazamiento
    }
}

/** `animate-pop` de la web: aparece bajando 6 dp con una escala casi imperceptible. */
fun Modifier.aparicionPop(): Modifier = composed {
    val progreso = remember { Animatable(0f) }
    LaunchedEffect(Unit) { progreso.animateTo(1f, tween(220, easing = CurvaLumen)) }
    val desplazamiento = with(LocalDensity.current) { 6.dp.toPx() }
    graphicsLayer {
        alpha = progreso.value
        translationY = -(1f - progreso.value) * desplazamiento
        val escala = 0.98f + 0.02f * progreso.value
        scaleX = escala
        scaleY = escala
    }
}

/** Respuesta táctil de los botones y tarjetas de la web (`hover:-translate-y`): se hunde al presionar. */
fun Modifier.efectoPresion(interaccion: InteractionSource, escalaPresionado: Float = 0.97f): Modifier = composed {
    val presionado by interaccion.collectIsPressedAsState()
    val escala by animateFloatAsState(
        targetValue = if (presionado) escalaPresionado else 1f,
        animationSpec = spring(dampingRatio = Spring.DampingRatioMediumBouncy, stiffness = Spring.StiffnessMedium),
        label = "presion",
    )
    graphicsLayer {
        scaleX = escala
        scaleY = escala
    }
}
