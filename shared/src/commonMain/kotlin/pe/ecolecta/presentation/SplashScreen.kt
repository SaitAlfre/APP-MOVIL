package pe.ecolecta.presentation

import androidx.compose.animation.core.FastOutSlowInEasing
import androidx.compose.animation.core.animateFloatAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.scale
import androidx.compose.ui.unit.dp
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EcolectaLogo
import pe.ecolecta.presentation.design.Espaciado

/**
 * Se muestra al abrir la app mientras se resuelve la sesión real (ver [App]: la misma
 * corrutina que decide a qué pantalla ir es la que mantiene visible el Splash). No agrega
 * ningún retraso artificial: en cuanto esa comprobación termina, [App] cambia de pantalla.
 */
@Composable
fun SplashScreen(modifier: Modifier = Modifier) {
    var animado by remember { mutableStateOf(false) }
    LaunchedEffect(Unit) { animado = true }

    val escala by animateFloatAsState(
        targetValue = if (animado) 1f else 0.85f,
        animationSpec = tween(durationMillis = 450, easing = FastOutSlowInEasing),
        label = "splashEscala",
    )
    val alfa by animateFloatAsState(
        targetValue = if (animado) 1f else 0f,
        animationSpec = tween(durationMillis = 450, easing = FastOutSlowInEasing),
        label = "splashAlfa",
    )

    Box(modifier.fillMaxSize().background(Colores.bgBase), contentAlignment = Alignment.Center) {
        Column(
            modifier = Modifier.scale(escala).alpha(alfa),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            EcolectaLogo(tamano = 104.dp)
            Text("Ecolecta", style = MaterialTheme.typography.headlineMedium, color = Colores.textPrimary)
            Text(
                "Gestión de acopio de leche",
                style = MaterialTheme.typography.bodyMedium,
                color = Colores.textSecundario,
            )
        }
    }
}
