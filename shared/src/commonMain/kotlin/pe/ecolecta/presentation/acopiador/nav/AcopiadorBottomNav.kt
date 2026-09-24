package pe.ecolecta.presentation.acopiador.nav

import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Groups
import androidx.compose.material.icons.outlined.LocalShipping
import androidx.compose.material.icons.outlined.Person
import androidx.compose.material.icons.outlined.Sync
import androidx.compose.material.icons.outlined.WaterDrop
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import pe.ecolecta.presentation.design.BarraNavegacionInferior
import pe.ecolecta.presentation.design.ItemNavegacion
import pe.ecolecta.presentation.navegacion.Pantalla

enum class PestanaAcopiador(val pantalla: Pantalla, val etiqueta: String, val icono: ImageVector) {
    JORNADA(Pantalla.AcopiadorHome, "Jornada", Icons.Outlined.LocalShipping),
    PROVEEDORES(Pantalla.AcopiadorLista, "Proveedores", Icons.Outlined.Groups),
    ENTREGAS(Pantalla.AcopiadorEntregas, "Entregas", Icons.Outlined.WaterDrop),
    SINCRONIZACION(Pantalla.AcopiadorSincronizacion, "Sincronizar", Icons.Outlined.Sync),
    PERFIL(Pantalla.AcopiadorPerfil, "Perfil", Icons.Outlined.Person),
}

@Composable
fun AcopiadorBottomNav(
    pestanaActual: PestanaAcopiador,
    onSeleccionar: (PestanaAcopiador) -> Unit,
    modifier: Modifier = Modifier,
    pendientesSync: Int = 0,
) {
    val pestanas = PestanaAcopiador.entries
    BarraNavegacionInferior(
        items = pestanas.map {
            ItemNavegacion(it.etiqueta, it.icono, if (it == PestanaAcopiador.SINCRONIZACION) pendientesSync else 0)
        },
        seleccionado = pestanas.indexOf(pestanaActual),
        onSeleccionar = { onSeleccionar(pestanas[it]) },
        modifier = modifier,
    )
}
