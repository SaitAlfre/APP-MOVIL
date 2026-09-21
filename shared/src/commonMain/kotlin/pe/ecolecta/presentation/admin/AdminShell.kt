package pe.ecolecta.presentation.admin

import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Menu
import androidx.compose.material3.DrawerValue
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.ModalNavigationDrawer
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.TopAppBarDefaults
import androidx.compose.material3.rememberDrawerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import pe.ecolecta.presentation.admin.auditoria.AuditoriaScreen
import pe.ecolecta.presentation.admin.conflictos.ConflictosScreen
import pe.ecolecta.presentation.admin.dashboard.AdminDashboardScreen
import pe.ecolecta.presentation.admin.entregas.EntregaDetalleScreen
import pe.ecolecta.presentation.admin.entregas.EntregasScreen
import pe.ecolecta.presentation.admin.jornadas.JornadaDetalleScreen
import pe.ecolecta.presentation.admin.jornadas.JornadasScreen
import pe.ecolecta.presentation.admin.nav.AdminDrawerContenido
import pe.ecolecta.presentation.admin.nav.AdminNavigationRail
import pe.ecolecta.presentation.admin.proveedores.ProveedorFormScreen
import pe.ecolecta.presentation.admin.proveedores.ProveedoresScreen
import pe.ecolecta.presentation.admin.traslados.TrasladosScreen
import pe.ecolecta.presentation.admin.usuarios.UsuarioFormScreen
import pe.ecolecta.presentation.admin.usuarios.UsuariosScreen
import pe.ecolecta.presentation.admin.vehiculos.VehiculoFormScreen
import pe.ecolecta.presentation.admin.vehiculos.VehiculosScreen
import pe.ecolecta.presentation.admin.zonas.ZonaFormScreen
import pe.ecolecta.presentation.admin.zonas.ZonasScreen
import pe.ecolecta.presentation.ConAlcancePorPantalla
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.navegacion.SeccionAdmin

private val ANCHO_TABLET = 840.dp

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AdminShell(
    pantalla: Pantalla,
    onCambiarPantalla: (Pantalla) -> Unit,
    onCerrarSesion: () -> Unit,
) {
    val seccionActual = seccionDe(pantalla)

    BoxWithConstraints(Modifier.fillMaxSize()) {
        if (maxWidth >= ANCHO_TABLET) {
            Row(Modifier.fillMaxSize()) {
                AdminNavigationRail(
                    seccionActual = seccionActual,
                    onSeleccionar = { onCambiarPantalla(it.pantalla) },
                    onCerrarSesion = onCerrarSesion,
                )
                androidx.compose.foundation.layout.Box(Modifier.weight(1f).fillMaxSize()) {
                    ContenidoAdmin(pantalla, onCambiarPantalla)
                }
            }
        } else {
            val estadoDrawer = rememberDrawerState(DrawerValue.Closed)
            val scope = rememberCoroutineScope()

            ModalNavigationDrawer(
                drawerState = estadoDrawer,
                drawerContent = {
                    AdminDrawerContenido(
                        seccionActual = seccionActual,
                        onSeleccionar = {
                            onCambiarPantalla(it.pantalla)
                            scope.launch { estadoDrawer.close() }
                        },
                        onCerrarSesion = onCerrarSesion,
                    )
                },
            ) {
                Scaffold(
                    topBar = {
                        TopAppBar(
                            title = { Text(seccionActual.etiqueta) },
                            navigationIcon = {
                                IconButton(onClick = { scope.launch { estadoDrawer.open() } }) {
                                    Icon(Icons.Filled.Menu, contentDescription = "Menú")
                                }
                            },
                            actions = {
                                IconButton(onClick = onCerrarSesion) {
                                    Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = "Cerrar sesión", tint = Colores.peligro)
                                }
                            },
                            colors = TopAppBarDefaults.topAppBarColors(containerColor = Colores.surface, titleContentColor = Colores.textPrimary),
                        )
                    },
                    containerColor = Colores.bgBase,
                ) { padding ->
                    androidx.compose.foundation.layout.Box(Modifier.fillMaxSize().padding(padding)) {
                        ContenidoAdmin(pantalla, onCambiarPantalla)
                    }
                }
            }
        }
    }
}

@Composable
private fun ContenidoAdmin(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    // Cada pantalla recibe su propio ViewModelStoreOwner: al navegar a otra, el anterior se limpia,
    // así los ViewModels de admin no se acumulan indefinidamente durante toda la sesión (§rendimiento).
    ConAlcancePorPantalla(pantalla) {
        ContenidoAdminPorPantalla(pantalla, onCambiarPantalla)
    }
}

@Composable
private fun ContenidoAdminPorPantalla(pantalla: Pantalla, onCambiarPantalla: (Pantalla) -> Unit) {
    when (pantalla) {
        Pantalla.AdminDashboard -> AdminDashboardScreen(alNavegar = onCambiarPantalla)
        is Pantalla.AdminCalidad -> pe.ecolecta.presentation.admin.supervision.AdminCalidadScreen(
            proveedorId = pantalla.proveedorId,
            alVolver = { onCambiarPantalla(pantalla.proveedorId?.let { Pantalla.AdminProveedorDetalle(it) } ?: Pantalla.AdminDashboard) },
        )
        is Pantalla.AdminProveedorDetalle -> pe.ecolecta.presentation.admin.supervision.AdminProveedorDetalleScreen(
            id = pantalla.id,
            alVolver = { onCambiarPantalla(Pantalla.AdminProveedores) },
            alEditar = { onCambiarPantalla(Pantalla.AdminProveedorForm(pantalla.id)) },
            alCalidad = { onCambiarPantalla(Pantalla.AdminCalidad(pantalla.id)) },
            alEntrega = { onCambiarPantalla(Pantalla.AdminEntregaDetalle(it, pantalla.id)) },
        )
        Pantalla.AdminUsuarios -> UsuariosScreen(
            alCrear = { onCambiarPantalla(Pantalla.AdminUsuarioForm()) },
            alEditar = { id -> onCambiarPantalla(Pantalla.AdminUsuarioForm(id)) },
        )
        is Pantalla.AdminUsuarioForm -> UsuarioFormScreen(
            id = pantalla.id,
            alGuardar = { onCambiarPantalla(Pantalla.AdminUsuarios) },
        )
        Pantalla.AdminZonas -> ZonasScreen(
            alCrear = { onCambiarPantalla(Pantalla.AdminZonaForm()) },
            alEditar = { id -> onCambiarPantalla(Pantalla.AdminZonaForm(id)) },
        )
        is Pantalla.AdminZonaForm -> ZonaFormScreen(
            id = pantalla.id,
            alGuardar = { onCambiarPantalla(Pantalla.AdminZonas) },
        )
        Pantalla.AdminVehiculos -> VehiculosScreen(
            alCrear = { onCambiarPantalla(Pantalla.AdminVehiculoForm()) },
            alEditar = { id -> onCambiarPantalla(Pantalla.AdminVehiculoForm(id)) },
        )
        is Pantalla.AdminVehiculoForm -> VehiculoFormScreen(
            id = pantalla.id,
            alGuardar = { onCambiarPantalla(Pantalla.AdminVehiculos) },
        )
        Pantalla.AdminProveedores -> ProveedoresScreen(
            alCrear = { onCambiarPantalla(Pantalla.AdminProveedorForm()) },
            alEditar = { id -> onCambiarPantalla(Pantalla.AdminProveedorDetalle(id)) },
        )
        is Pantalla.AdminProveedorForm -> ProveedorFormScreen(
            id = pantalla.id,
            alGuardar = { onCambiarPantalla(pantalla.id?.let { Pantalla.AdminProveedorDetalle(it) } ?: Pantalla.AdminProveedores) },
        )
        Pantalla.AdminTraslados -> TrasladosScreen()
        Pantalla.AdminJornadas -> JornadasScreen(
            alVerDetalle = { id -> onCambiarPantalla(Pantalla.AdminJornadaDetalle(id)) },
        )
        is Pantalla.AdminJornadaDetalle -> JornadaDetalleScreen(
            id = pantalla.id,
            alVerEntrega = { id -> onCambiarPantalla(Pantalla.AdminEntregaDetalle(id)) },
            alVolver = { onCambiarPantalla(Pantalla.AdminJornadas) },
        )
        Pantalla.AdminEntregas -> EntregasScreen(
            alVerDetalle = { id -> onCambiarPantalla(Pantalla.AdminEntregaDetalle(id)) },
        )
        is Pantalla.AdminEntregaDetalle -> EntregaDetalleScreen(
            id = pantalla.id,
            alVolver = { onCambiarPantalla(pantalla.proveedorOrigenId?.let { Pantalla.AdminProveedorDetalle(it) } ?: Pantalla.AdminEntregas) },
        )
        Pantalla.AdminConflictos -> ConflictosScreen()
        Pantalla.AdminAuditoria -> AuditoriaScreen()
        else -> AdminDashboardScreen()
    }
}

private fun seccionDe(pantalla: Pantalla): SeccionAdmin = when (pantalla) {
    Pantalla.AdminDashboard -> SeccionAdmin.DASHBOARD
    Pantalla.AdminUsuarios, is Pantalla.AdminUsuarioForm -> SeccionAdmin.USUARIOS
    Pantalla.AdminZonas, is Pantalla.AdminZonaForm -> SeccionAdmin.ZONAS
    Pantalla.AdminVehiculos, is Pantalla.AdminVehiculoForm -> SeccionAdmin.VEHICULOS
    Pantalla.AdminProveedores, is Pantalla.AdminProveedorForm, is Pantalla.AdminProveedorDetalle -> SeccionAdmin.PROVEEDORES
    is Pantalla.AdminCalidad -> SeccionAdmin.CALIDAD
    Pantalla.AdminTraslados -> SeccionAdmin.TRASLADOS
    Pantalla.AdminJornadas, is Pantalla.AdminJornadaDetalle -> SeccionAdmin.JORNADAS
    Pantalla.AdminEntregas, is Pantalla.AdminEntregaDetalle -> SeccionAdmin.ENTREGAS
    Pantalla.AdminConflictos -> SeccionAdmin.CONFLICTOS
    Pantalla.AdminAuditoria -> SeccionAdmin.AUDITORIA
    else -> SeccionAdmin.DASHBOARD
}
