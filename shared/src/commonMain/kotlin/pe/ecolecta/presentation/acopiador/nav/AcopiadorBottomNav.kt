package pe.ecolecta.presentation.acopiador.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ListAlt
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.navegacion.Pantalla

enum class PestanaAcopiador(val pantalla: Pantalla, val etiqueta: String, val icono: ImageVector) {
    INICIO(Pantalla.AcopiadorHome, "Inicio", Icons.Filled.Home),
    LISTA(Pantalla.AcopiadorLista, "Lista", Icons.AutoMirrored.Filled.ListAlt),
    SINCRONIZACION(Pantalla.AcopiadorSincronizacion, "Sincronizar", Icons.Filled.Sync),
    PERFIL(Pantalla.AcopiadorPerfil, "Perfil", Icons.Filled.Person),
}

@Composable
fun AcopiadorBottomNav(pestanaActual: PestanaAcopiador, onSeleccionar: (PestanaAcopiador) -> Unit, modifier: Modifier = Modifier) {
    NavigationBar(modifier = modifier, containerColor = Colores.surface) {
        PestanaAcopiador.entries.forEach { pestana ->
            NavigationBarItem(
                selected = pestana == pestanaActual,
                onClick = { onSeleccionar(pestana) },
                icon = { Icon(pestana.icono, contentDescription = pestana.etiqueta) },
                label = { Text(pestana.etiqueta) },
                colors = NavigationBarItemDefaults.colors(
                    selectedIconColor = Colores.brandText,
                    indicatorColor = Color.Transparent,
                    selectedTextColor = Colores.brandText,
                    unselectedIconColor = Colores.textSecundario,
                    unselectedTextColor = Colores.textSecundario,
                ),
            )
        }
    }
}
