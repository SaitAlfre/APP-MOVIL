package pe.ecolecta.presentation.proveedor.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ReceiptLong
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Map
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.QrCode2
import androidx.compose.material.icons.filled.CheckCircleOutline
import androidx.compose.material.icons.filled.AttachMoney
import androidx.compose.material.icons.filled.WaterDrop
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

enum class PestanaProveedor(val pantalla: Pantalla, val etiqueta: String, val icono: ImageVector) {
    INICIO(Pantalla.ProveedorHome, "Inicio", Icons.Filled.Home),
    ENTREGAS(Pantalla.ProveedorEntregas, "Entregas", Icons.Filled.WaterDrop),
    CALIDAD(Pantalla.ProveedorCalidad, "Calidad", Icons.Filled.CheckCircleOutline),
    PAGOS(Pantalla.ProveedorPagos, "Pagos", Icons.Filled.AttachMoney),
    PERFIL(Pantalla.ProveedorPerfil, "Perfil", Icons.Filled.Person),
}

@Composable
fun ProveedorBottomNav(pestanaActual: PestanaProveedor, onSeleccionar: (PestanaProveedor) -> Unit, modifier: Modifier = Modifier) {
    NavigationBar(modifier = modifier, containerColor = Colores.surface) {
        PestanaProveedor.entries.forEach { pestana ->
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
