package pe.ecolecta.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Construction
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado

/** CALIDAD, ASISTENTE, PRODUCCIÓN y DESPACHO no tienen módulo propio todavía (fuera del alcance de esta fase). */
@Composable
fun RolNoDisponibleScreen(alCerrarSesion: () -> Unit) {
    Box(Modifier.fillMaxSize().padding(Espaciado.xl), contentAlignment = Alignment.Center) {
        Column(
            modifier = Modifier.widthIn(max = 360.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Box(
                Modifier.size(64.dp).clip(CircleShape).background(Colores.surfaceAlta),
                contentAlignment = Alignment.Center,
            ) {
                Icon(Icons.Filled.Construction, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.size(32.dp))
            }
            Text(
                "Módulo no disponible",
                style = MaterialTheme.typography.headlineSmall,
                color = Colores.textPrimary,
                textAlign = TextAlign.Center,
            )
            Text(
                "Este rol todavía no tiene una pantalla propia en la app.",
                style = MaterialTheme.typography.bodyMedium,
                color = Colores.textSecundario,
                textAlign = TextAlign.Center,
            )
            BotonSecundario("Cerrar sesión", alCerrarSesion)
        }
    }
}
