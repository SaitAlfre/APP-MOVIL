package pe.ecolecta.presentation.admin.nav

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.BarChart
import androidx.compose.material.icons.outlined.Home
import androidx.compose.material.icons.outlined.LocalShipping
import androidx.compose.material.icons.outlined.Notifications
import androidx.compose.material.icons.outlined.Person
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.navegacion.Pantalla

enum class PestanaAdmin(val pantalla: Pantalla, val etiqueta: String, val icono: ImageVector) {
    INICIO(Pantalla.AdminDashboard, "Inicio", Icons.Outlined.Home),
    JORNADAS(Pantalla.AdminJornadas, "Jornadas", Icons.Outlined.LocalShipping),
    ALERTAS(Pantalla.AdminAlertas(), "Alertas", Icons.Outlined.Notifications),
    REPORTES(Pantalla.AdminReportes, "Reportes", Icons.Outlined.BarChart),
    PERFIL(Pantalla.AdminPerfil, "Perfil", Icons.Outlined.Person),
}

/** Barra inferior del prototipo: blanca, 68 dp, ícono de trazo y etiqueta de 10 sp. */
@Composable
fun AdminBottomNav(pestanaActual: PestanaAdmin, alertas: Int, onSeleccionar: (PestanaAdmin) -> Unit) {
    Column(Modifier.fillMaxWidth().background(AdminColor.blanco).navigationBarsPadding()) {
        Box(Modifier.fillMaxWidth().height(1.dp).background(AdminColor.borde))
        Row(Modifier.fillMaxWidth().height(68.dp)) {
            PestanaAdmin.entries.forEach { pestana ->
                val activa = pestana == pestanaActual
                val color = if (activa) AdminColor.verde else AdminColor.gris
                Column(
                    Modifier.weight(1f).fillMaxHeight().clickable(
                        interactionSource = remember { MutableInteractionSource() },
                        indication = null,
                        role = Role.Tab,
                    ) { onSeleccionar(pestana) },
                    horizontalAlignment = Alignment.CenterHorizontally,
                    verticalArrangement = Arrangement.spacedBy(2.dp, Alignment.CenterVertically),
                ) {
                    Box(Modifier.size(24.dp)) {
                        Icon(pestana.icono, contentDescription = pestana.etiqueta, tint = color, modifier = Modifier.size(24.dp))
                        if (pestana == PestanaAdmin.ALERTAS && alertas > 0) {
                            Text(
                                if (alertas > 99) "99+" else alertas.toString(),
                                modifier = Modifier.align(Alignment.TopEnd).offset(x = 10.dp, y = (-4).dp)
                                    .clip(RoundedCornerShape(10.dp)).background(AdminColor.rojo)
                                    .widthIn(min = 16.dp).padding(horizontal = 4.dp, vertical = 1.dp),
                                color = AdminColor.blanco,
                                fontSize = 10.sp,
                                fontWeight = FontWeight.Bold,
                                textAlign = TextAlign.Center,
                            )
                        }
                    }
                    Text(pestana.etiqueta, color = color, fontSize = 10.sp, fontWeight = FontWeight.Medium)
                }
            }
        }
    }
}
