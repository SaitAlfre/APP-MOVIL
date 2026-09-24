package pe.ecolecta.presentation.proveedor.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.CheckCircle
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.Payments
import androidx.compose.material.icons.outlined.Person
import androidx.compose.material.icons.outlined.WaterDrop
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import pe.ecolecta.presentation.design.BarraNavegacionInferior
import pe.ecolecta.presentation.design.ItemNavegacion
import pe.ecolecta.presentation.navegacion.Pantalla

enum class PestanaProveedor(val pantalla: Pantalla, val etiqueta: String, val icono: ImageVector) {
    INICIO(Pantalla.ProveedorHome, "Inicio", Icons.Outlined.Home),
    ENTREGAS(Pantalla.ProveedorEntregas, "Entregas", Icons.Outlined.WaterDrop),
    CALIDAD(Pantalla.ProveedorCalidad, "Calidad", Icons.Outlined.CheckCircle),
    PAGOS(Pantalla.ProveedorPagos, "Pagos", Icons.Outlined.Payments),
    PERFIL(Pantalla.ProveedorPerfil, "Perfil", Icons.Outlined.Person),
}

@Composable
fun ProveedorBottomNav(pestanaActual: PestanaProveedor, onSeleccionar: (PestanaProveedor) -> Unit, modifier: Modifier = Modifier) {
    val pestanas = PestanaProveedor.entries
    BarraNavegacionInferior(
        items = pestanas.map { ItemNavegacion(it.etiqueta, it.icono) },
        seleccionado = pestanas.indexOf(pestanaActual),
        onSeleccionar = { onSeleccionar(pestanas[it]) },
        modifier = modifier,
    )
}
