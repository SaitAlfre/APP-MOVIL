package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Scaffold
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.proveedor.detalle.DetalleEntregaProveedorScreen
import pe.ecolecta.presentation.proveedor.entregas.MisEntregasScreen
import pe.ecolecta.presentation.proveedor.home.ProveedorHomeScreen
import pe.ecolecta.presentation.proveedor.nav.PestanaProveedor
import pe.ecolecta.presentation.proveedor.nav.ProveedorBottomNav
import pe.ecolecta.presentation.proveedor.perfil.PerfilProveedorScreen

@Composable
fun ProveedorShell(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    val esDetalle = pantalla is Pantalla.ProveedorEntregaDetalle

    Scaffold(
        containerColor = Colores.bgBase,
        bottomBar = {
            if (!esDetalle) {
                ProveedorBottomNav(
                    pestanaActual = pestanaDe(pantalla),
                    onSeleccionar = { onCambiarPantalla(it.pantalla) },
                )
            }
        },
    ) { paddingInterno ->
        Column(Modifier.fillMaxSize().padding(paddingInterno)) {
            when (pantalla) {
                Pantalla.ProveedorHome -> ProveedorHomeScreen()
                Pantalla.ProveedorEntregas -> MisEntregasScreen(
                    alVerDetalle = { id -> onCambiarPantalla(Pantalla.ProveedorEntregaDetalle(id)) },
                )
                is Pantalla.ProveedorEntregaDetalle -> {
                    BarraSuperior(
                        titulo = "Detalle de entrega",
                        alVolver = { onCambiarPantalla(Pantalla.ProveedorEntregas) },
                    )
                    DetalleEntregaProveedorScreen(id = pantalla.id)
                }
                Pantalla.ProveedorPerfil -> PerfilProveedorScreen()
                else -> ProveedorHomeScreen()
            }
        }
    }
}

private fun pestanaDe(pantalla: Pantalla): PestanaProveedor = when (pantalla) {
    Pantalla.ProveedorEntregas -> PestanaProveedor.ENTREGAS
    Pantalla.ProveedorPerfil -> PestanaProveedor.PERFIL
    else -> PestanaProveedor.INICIO
}
