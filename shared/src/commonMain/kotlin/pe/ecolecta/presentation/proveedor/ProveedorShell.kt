package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.calidad.CalidadBackHandler
import pe.ecolecta.presentation.design.EcolectaTheme
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.proveedor.nav.*
import pe.ecolecta.presentation.proveedor.ruta.MiRutaAcopioScreen

@Composable
fun ProveedorShell(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit, vm: PortalProveedorViewModel = koinViewModel()) {
    val s by vm.state.collectAsState()
    CalidadBackHandler(pantalla != Pantalla.ProveedorHome) { onCambiarPantalla(Pantalla.ProveedorHome) }
    EcolectaTheme(oscuroForzado = false) {
        Scaffold(containerColor = ProveedorFondo, bottomBar = {
            ProveedorBottomNav(when(pantalla) {
                Pantalla.ProveedorEntregas, is Pantalla.ProveedorEntregaDetalle -> PestanaProveedor.ENTREGAS
                Pantalla.ProveedorCalidad -> PestanaProveedor.CALIDAD
                Pantalla.ProveedorPagos -> PestanaProveedor.PAGOS
                Pantalla.ProveedorPerfil -> PestanaProveedor.PERFIL
                else -> PestanaProveedor.INICIO
            }, { vm.limpiarMensaje(); onCambiarPantalla(it.pantalla) })
        }) { padding ->
            Box(Modifier.fillMaxSize().padding(padding)) {
                if (s.cargando) CargandoProveedor()
                else if (s.proveedor == null) ErrorProveedor(s.error ?: "No se encontró tu perfil.", vm::recargar)
                else when(pantalla) {
                    Pantalla.ProveedorHome -> InicioProveedor(s, onCambiarPantalla)
                    Pantalla.ProveedorEntregas -> EntregasProveedor(s, onCambiarPantalla)
                    is Pantalla.ProveedorEntregaDetalle -> DetalleProveedor(s, pantalla.id, onCambiarPantalla)
                    Pantalla.ProveedorCalidad -> CalidadProveedor(s, onCambiarPantalla)
                    Pantalla.ProveedorPagos -> PagosProveedor(s, onCambiarPantalla)
                    Pantalla.ProveedorMiQr -> QrProveedor(s, onCambiarPantalla)
                    Pantalla.ProveedorReclamos -> ReclamoProveedor(s, vm, onCambiarPantalla)
                    Pantalla.ProveedorTraslado -> TrasladoProveedor(s, vm, onCambiarPantalla)
                    Pantalla.ProveedorSolicitudes -> SolicitudesProveedor(s, onCambiarPantalla)
                    Pantalla.ProveedorPerfil -> PerfilProveedor(s, vm, onCambiarPantalla)
                    Pantalla.ProveedorMiRuta -> MiRutaAcopioScreen()
                    else -> InicioProveedor(s, onCambiarPantalla)
                }
            }
        }
    }
}
