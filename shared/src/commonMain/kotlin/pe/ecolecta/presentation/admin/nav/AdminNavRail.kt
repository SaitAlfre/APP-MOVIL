package pe.ecolecta.presentation.admin.nav

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Dashboard
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.History
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.Opacity
import androidx.compose.material.icons.filled.Place
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material.icons.filled.SwapHoriz
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalDrawerSheet
import androidx.compose.material3.NavigationDrawerItem
import androidx.compose.material3.NavigationDrawerItemDefaults
import androidx.compose.material3.NavigationRail
import androidx.compose.material3.NavigationRailItem
import androidx.compose.material3.NavigationRailItemDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.navegacion.SeccionAdmin

private fun iconoDeSeccion(seccion: SeccionAdmin): ImageVector = when (seccion) {
    SeccionAdmin.DASHBOARD -> Icons.Filled.Dashboard
    SeccionAdmin.USUARIOS -> Icons.Filled.Group
    SeccionAdmin.ZONAS -> Icons.Filled.Place
    SeccionAdmin.VEHICULOS -> Icons.Filled.LocalShipping
    SeccionAdmin.PROVEEDORES -> Icons.Filled.Storefront
    SeccionAdmin.TRASLADOS -> Icons.Filled.SwapHoriz
    SeccionAdmin.JORNADAS -> Icons.Filled.CalendarMonth
    SeccionAdmin.ENTREGAS -> Icons.Filled.Opacity
    SeccionAdmin.CONFLICTOS -> Icons.Filled.WarningAmber
    SeccionAdmin.AUDITORIA -> Icons.Filled.History
}

/** Riel de navegación para pantallas anchas (tablet/desktop): íconos + etiqueta, siempre visible. */
@Composable
fun AdminNavigationRail(
    seccionActual: SeccionAdmin,
    onSeleccionar: (SeccionAdmin) -> Unit,
    onCerrarSesion: () -> Unit,
    modifier: Modifier = Modifier,
) {
    NavigationRail(modifier = modifier.width(96.dp), containerColor = Colores.surface) {
        Column(
            Modifier.fillMaxHeight().verticalScroll(rememberScrollState()).padding(vertical = Espaciado.s),
        ) {
            SeccionAdmin.entries.forEach { seccion ->
                NavigationRailItem(
                    selected = seccion == seccionActual,
                    onClick = { onSeleccionar(seccion) },
                    icon = { Icon(iconoDeSeccion(seccion), contentDescription = seccion.etiqueta) },
                    label = { Text(seccion.etiqueta, style = MaterialTheme.typography.labelSmall) },
                    colors = NavigationRailItemDefaults.colors(
                        selectedIconColor = Colores.onBrand,
                        indicatorColor = Colores.brand,
                        selectedTextColor = Colores.brandText,
                        unselectedIconColor = Colores.textSecundario,
                        unselectedTextColor = Colores.textSecundario,
                    ),
                )
            }
            NavigationRailItem(
                selected = false,
                onClick = onCerrarSesion,
                icon = { Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = "Cerrar sesión", tint = Colores.peligro) },
                label = { Text("Salir", color = Colores.peligro, style = MaterialTheme.typography.labelSmall) },
            )
        }
    }
}

/** Contenido del drawer para teléfonos: lista de secciones con ícono, dentro de un ModalDrawerSheet. */
@Composable
fun AdminDrawerContenido(
    seccionActual: SeccionAdmin,
    onSeleccionar: (SeccionAdmin) -> Unit,
    onCerrarSesion: () -> Unit,
) {
    ModalDrawerSheet(drawerContainerColor = Colores.surface) {
        Text(
            "ECOLECTA HUATA",
            color = Colores.brandText,
            style = MaterialTheme.typography.titleLarge,
            modifier = Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.l),
        )
        SeccionAdmin.entries.forEach { seccion ->
            NavigationDrawerItem(
                label = { Text(seccion.etiqueta) },
                icon = { Icon(iconoDeSeccion(seccion), contentDescription = null) },
                selected = seccion == seccionActual,
                onClick = { onSeleccionar(seccion) },
                colors = NavigationDrawerItemDefaults.colors(
                    selectedContainerColor = Colores.brandContainer,
                    selectedIconColor = Colores.onBrandContainer,
                    selectedTextColor = Colores.onBrandContainer,
                    unselectedIconColor = Colores.textSecundario,
                    unselectedTextColor = Colores.textPrimary,
                ),
                modifier = Modifier.padding(horizontal = Espaciado.s, vertical = Espaciado.xxs),
            )
        }
        HorizontalDivider(Modifier.padding(vertical = Espaciado.s), color = Colores.borde)
        NavigationDrawerItem(
            label = { Text("Cerrar sesión", color = Colores.peligro) },
            icon = { Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null, tint = Colores.peligro) },
            selected = false,
            onClick = onCerrarSesion,
            modifier = Modifier.padding(horizontal = Espaciado.s),
        )
    }
}
