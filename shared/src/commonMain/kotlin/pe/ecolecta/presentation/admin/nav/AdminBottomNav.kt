package pe.ecolecta.presentation.admin.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.BarChart
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.LocalShipping
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material.icons.outlined.Person
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.vector.ImageVector
import pe.ecolecta.presentation.design.BarraNavegacionInferior
import pe.ecolecta.presentation.design.ItemNavegacion
import pe.ecolecta.presentation.navegacion.Pantalla

enum class PestanaAdmin(val pantalla: Pantalla, val etiqueta: String, val icono: ImageVector) {
    INICIO(Pantalla.AdminDashboard, "Inicio", Icons.Outlined.Home),
    JORNADAS(Pantalla.AdminJornadas, "Jornadas", Icons.Outlined.LocalShipping),
    ALERTAS(Pantalla.AdminAlertas(), "Alertas", Icons.Outlined.Notifications),
    REPORTES(Pantalla.AdminReportes, "Reportes", Icons.Outlined.BarChart),
    PERFIL(Pantalla.AdminPerfil, "Perfil", Icons.Outlined.Person),
}

/** Barra inferior del Administrador con el estilo del menú lateral tinta del panel web. */
@Composable
fun AdminBottomNav(pestanaActual: PestanaAdmin, alertas: Int, onSeleccionar: (PestanaAdmin) -> Unit) {
    val pestanas = PestanaAdmin.entries
    BarraNavegacionInferior(
        items = pestanas.map { ItemNavegacion(it.etiqueta, it.icono, if (it == PestanaAdmin.ALERTAS) alertas else 0) },
        seleccionado = pestanas.indexOf(pestanaActual),
        onSeleccionar = { onSeleccionar(pestanas[it]) },
    )
}
