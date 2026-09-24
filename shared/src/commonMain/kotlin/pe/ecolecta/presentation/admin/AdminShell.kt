package pe.ecolecta.presentation.admin

import pe.ecolecta.presentation.design.entradaPantalla
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateListOf
import androidx.compose.runtime.remember
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.ConAlcancePorPantalla
import pe.ecolecta.presentation.admin.alertas.AdminAlertasScreen
import pe.ecolecta.presentation.admin.alertas.AdminAlertasViewModel
import pe.ecolecta.presentation.admin.auditoria.AuditoriaScreen
import pe.ecolecta.presentation.admin.conflictos.ConflictosScreen
import pe.ecolecta.presentation.admin.dashboard.AdminDashboardScreen
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.entregas.EntregaDetalleScreen
import pe.ecolecta.presentation.admin.entregas.EntregasScreen
import pe.ecolecta.presentation.admin.jornadas.JornadaDetalleScreen
import pe.ecolecta.presentation.admin.jornadas.JornadasScreen
import pe.ecolecta.presentation.admin.nav.AdminBottomNav
import pe.ecolecta.presentation.admin.nav.PestanaAdmin
import pe.ecolecta.presentation.admin.perfil.AdminPerfilScreen
import pe.ecolecta.presentation.admin.proveedores.ProveedorFormScreen
import pe.ecolecta.presentation.admin.proveedores.ProveedoresScreen
import pe.ecolecta.presentation.admin.reportes.AdminReportesScreen
import pe.ecolecta.presentation.admin.supervision.AdminCalidadScreen
import pe.ecolecta.presentation.admin.supervision.AdminProveedorDetalleScreen
import pe.ecolecta.presentation.admin.traslados.TrasladosScreen
import pe.ecolecta.presentation.admin.usuarios.UsuarioFormScreen
import pe.ecolecta.presentation.admin.usuarios.UsuariosScreen
import pe.ecolecta.presentation.admin.vehiculos.VehiculoFormScreen
import pe.ecolecta.presentation.admin.vehiculos.VehiculosScreen
import pe.ecolecta.presentation.admin.zonas.ZonaFormScreen
import pe.ecolecta.presentation.admin.zonas.ZonasScreen
import pe.ecolecta.presentation.calidad.CalidadBackHandler
import pe.ecolecta.presentation.navegacion.Pantalla

/**
 * Administrador con el diseño del prototipo: cinco pestañas (Inicio, Jornadas, Alertas,
 * Reportes, Perfil) y los módulos de gestión apilados encima, con historial de retroceso.
 */
@Composable
fun AdminShell(
    pantalla: Pantalla,
    onCambiarPantalla: (Pantalla) -> Unit,
    onCerrarSesion: () -> Unit,
) {
    val historial = remember { mutableStateListOf<Pantalla>() }
    // Una sola instancia de alertas para la insignia, Inicio y Alertas: vive en el ámbito de la sesión.
    val alertasViewModel: AdminAlertasViewModel = koinViewModel(key = "admin-alertas-insignia")
    val alertas by alertasViewModel.uiState.collectAsState()

    fun navegar(destino: Pantalla) {
        if (destino == pantalla) return
        val pestana = PestanaAdmin.entries.firstOrNull { it.pantalla == destino }
        if (pestana != null && destino !is Pantalla.AdminAlertas) historial.clear() else historial.add(pantalla)
        onCambiarPantalla(destino)
    }

    fun volver() {
        val anterior = historial.removeLastOrNull() ?: Pantalla.AdminDashboard
        onCambiarPantalla(anterior)
    }

    fun irAPestana(pestana: PestanaAdmin) {
        historial.clear()
        onCambiarPantalla(pestana.pantalla)
    }

    CalidadBackHandler(pantalla != Pantalla.AdminDashboard) { volver() }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        Box(Modifier.weight(1f).fillMaxWidth().statusBarsPadding().entradaPantalla(pantalla)) {
            // Cada pantalla recibe su propio ViewModelStoreOwner: al navegar a otra, el anterior se
            // limpia, así los ViewModels de admin no se acumulan durante la sesión (§rendimiento).
            ConAlcancePorPantalla(pantalla) {
                ContenidoAdmin(pantalla, alertasViewModel, ::navegar, ::volver, onCerrarSesion)
            }
        }
        AdminBottomNav(pestanaDe(pantalla), alertas.alertas.size, ::irAPestana)
    }
}

@Composable
private fun ContenidoAdmin(
    pantalla: Pantalla,
    alertas: AdminAlertasViewModel,
    navegar: (Pantalla) -> Unit,
    volver: () -> Unit,
    onCerrarSesion: () -> Unit,
) {
    when (pantalla) {
        Pantalla.AdminDashboard -> AdminDashboardScreen(alNavegar = navegar, alertasViewModel = alertas)
        Pantalla.AdminJornadas -> JornadasScreen(alVerDetalle = { navegar(Pantalla.AdminJornadaDetalle(it)) }, alVolver = volver)
        is Pantalla.AdminJornadaDetalle -> JornadaDetalleScreen(
            id = pantalla.id,
            alVerEntrega = { navegar(Pantalla.AdminEntregaDetalle(it)) },
            alVolver = volver,
        )
        is Pantalla.AdminAlertas -> AdminAlertasScreen(filtroInicial = pantalla.filtro, alNavegar = navegar, alVolver = volver, viewModel = alertas)
        Pantalla.AdminReportes -> AdminReportesScreen(alVolver = volver)
        Pantalla.AdminPerfil -> AdminPerfilScreen(alNavegar = navegar, alCerrarSesion = onCerrarSesion)

        is Pantalla.AdminCalidad -> AdminCalidadScreen(proveedorId = pantalla.proveedorId, alVolver = volver)
        is Pantalla.AdminProveedorDetalle -> AdminProveedorDetalleScreen(
            id = pantalla.id,
            alVolver = volver,
            alEditar = { navegar(Pantalla.AdminProveedorForm(pantalla.id)) },
            alCalidad = { navegar(Pantalla.AdminCalidad(pantalla.id)) },
            alEntrega = { navegar(Pantalla.AdminEntregaDetalle(it, pantalla.id)) },
        )
        Pantalla.AdminProveedores -> ProveedoresScreen(
            alEditar = { navegar(Pantalla.AdminProveedorDetalle(it)) },
            alVolver = volver,
        )
        is Pantalla.AdminProveedorForm -> ProveedorFormScreen(
            id = pantalla.id,
            alGuardar = volver,
            alVolver = volver,
            alCuenta = { usuarioId, fichaId -> navegar(Pantalla.AdminUsuarioForm(usuarioId, fichaId.takeIf { usuarioId == null })) },
        )
        Pantalla.AdminUsuarios -> UsuariosScreen(
            alCrear = { navegar(Pantalla.AdminUsuarioForm()) },
            alEditar = { navegar(Pantalla.AdminUsuarioForm(it)) },
            alVolver = volver,
        )
        is Pantalla.AdminUsuarioForm -> UsuarioFormScreen(id = pantalla.id, fichaId = pantalla.fichaId, alGuardar = volver, alVolver = volver)
        Pantalla.AdminZonas -> ZonasScreen(
            alCrear = { navegar(Pantalla.AdminZonaForm()) },
            alEditar = { navegar(Pantalla.AdminZonaForm(it)) },
            alVolver = volver,
        )
        is Pantalla.AdminZonaForm -> ZonaFormScreen(id = pantalla.id, alGuardar = volver, alVolver = volver)
        Pantalla.AdminVehiculos -> VehiculosScreen(
            alCrear = { navegar(Pantalla.AdminVehiculoForm()) },
            alEditar = { navegar(Pantalla.AdminVehiculoForm(it)) },
            alVolver = volver,
        )
        is Pantalla.AdminVehiculoForm -> VehiculoFormScreen(id = pantalla.id, alGuardar = volver, alVolver = volver)
        Pantalla.AdminTraslados -> TrasladosScreen(alVolver = volver)
        Pantalla.AdminEntregas -> EntregasScreen(alVerDetalle = { navegar(Pantalla.AdminEntregaDetalle(it)) }, alVolver = volver)
        is Pantalla.AdminEntregaDetalle -> EntregaDetalleScreen(id = pantalla.id, alVolver = volver)
        Pantalla.AdminConflictos -> ConflictosScreen(alVolver = volver)
        Pantalla.AdminAuditoria -> AuditoriaScreen(alVolver = volver)
        else -> AdminDashboardScreen(alNavegar = navegar, alertasViewModel = alertas)
    }
}

/** Pestaña resaltada: los módulos de gestión cuelgan de Inicio o de Perfil, según desde dónde se abren. */
private fun pestanaDe(pantalla: Pantalla): PestanaAdmin = when (pantalla) {
    Pantalla.AdminJornadas, is Pantalla.AdminJornadaDetalle -> PestanaAdmin.JORNADAS
    is Pantalla.AdminAlertas -> PestanaAdmin.ALERTAS
    Pantalla.AdminReportes -> PestanaAdmin.REPORTES
    Pantalla.AdminPerfil, Pantalla.AdminUsuarios, is Pantalla.AdminUsuarioForm, Pantalla.AdminZonas, is Pantalla.AdminZonaForm,
    Pantalla.AdminVehiculos, is Pantalla.AdminVehiculoForm, Pantalla.AdminAuditoria -> PestanaAdmin.PERFIL
    else -> PestanaAdmin.INICIO
}
