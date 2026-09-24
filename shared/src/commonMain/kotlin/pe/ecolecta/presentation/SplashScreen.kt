package pe.ecolecta.presentation

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
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import pe.ecolecta.presentation.design.Antetitulo
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.CurvaLumen
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
        animationSpec = tween(durationMillis = 600, easing = CurvaLumen),
        label = "splashEscala",
    )
    val alfa by animateFloatAsState(
        targetValue = if (animado) 1f else 0f,
        animationSpec = tween(durationMillis = 600, easing = CurvaLumen),
        label = "splashAlfa",
    )

    Box(modifier.fillMaxSize().background(Colores.tinta), contentAlignment = Alignment.Center) {
        Column(
            modifier = Modifier.scale(escala).alpha(alfa),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(Espaciado.xs),
        ) {
            EcolectaLogo(
                tamano = 88.dp,
                colorFondo = Colores.lima,
                colorSimbolo = Color(0xFF142820),
                forma = RoundedCornerShape(24.dp),
                modifier = Modifier.shadow(24.dp, RoundedCornerShape(24.dp), clip = false, ambientColor = Colores.lima, spotColor = Colores.lima),
            )
            Spacer(Modifier.height(Espaciado.s))
            Text("Ecolecta", style = MaterialTheme.typography.headlineLarge, color = Color.White)
            Antetitulo("Huata", color = Color.White.copy(alpha = 0.45f))
            Spacer(Modifier.height(Espaciado.xs))
            Text(
                "Gestión de acopio de leche",
                style = MaterialTheme.typography.bodySmall,
                color = Color.White.copy(alpha = 0.55f),
            )
        }
    }
}
