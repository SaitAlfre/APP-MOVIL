package pe.ecolecta.presentation.admin.perfil

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.outlined.Shield
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.dashboard.ObtenerResumenAdminUseCase
import pe.ecolecta.domain.usecase.dashboard.ResumenAdmin
import pe.ecolecta.presentation.admin.design.AdminBotonBorde
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.cargaSegura
import pe.ecolecta.presentation.navegacion.Pantalla

private const val VERSION_APP = "1.0"

data class AdminPerfilUiState(val nombre: String = "", val usuario: String = "", val resumen: ResumenAdmin? = null)

class AdminPerfilViewModel(
    obtenerSesion: ObtenerSesionUseCase,
    private val obtenerResumen: ObtenerResumenAdminUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AdminPerfilUiState())
    val uiState = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            obtenerSesion().collect { s -> _uiState.update { it.copy(nombre = s?.usuario?.nombres.orEmpty(), usuario = s?.usuario?.username.orEmpty()) } }
        }
        viewModelScope.launch { cargaSegura { obtenerResumen() }.onSuccess { r -> _uiState.update { it.copy(resumen = r) } } }
    }
}

@Composable
fun AdminPerfilScreen(
    alNavegar: (Pantalla) -> Unit,
    alCerrarSesion: () -> Unit,
    viewModel: AdminPerfilViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    var confirmarSalida by remember { mutableStateOf(false) }
    val r = estado.resumen

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Perfil")
        Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
            Row(
                Modifier.fillMaxWidth().background(AdminColor.verdeOscuro).padding(start = 20.dp, end = 20.dp, top = 24.dp, bottom = 40.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                Box(Modifier.size(56.dp).clip(CircleShape).background(Color.White.copy(alpha = 0.2f)), contentAlignment = Alignment.Center) {
                    Icon(Icons.Outlined.Shield, contentDescription = null, tint = Color.White, modifier = Modifier.size(28.dp))
                }
                Column {
                    AdminTexto(estado.nombre.ifBlank { "Administrador" }, 18, Color.White, FontWeight.Bold)
                    AdminTexto("${estado.usuario.ifBlank { "—" }} · Administrador", 13, Color.White.copy(alpha = 0.7f))
                }
            }
            Column(Modifier.offset(y = (-16).dp).padding(horizontal = 16.dp)) {
                ListaDatos(
                    listOf(
                        Triple("Jornadas activas", r?.jornadasAbiertas?.toString() ?: "—", AdminColor.texto),
                        Triple("Proveedores activos", r?.proveedoresActivos?.toString() ?: "—", AdminColor.texto),
                        Triple("Acopiadores habilitados", r?.acopiadoresActivos?.toString() ?: "—", AdminColor.texto),
                        Triple("Datos", "Guardados en este dispositivo", AdminColor.verde),
                        Triple("Pendientes de envío", r?.entregasPendientes?.toString() ?: "—", if ((r?.entregasPendientes ?: 0) > 0) AdminColor.ambarTexto else AdminColor.texto),
                        Triple("Versión", VERSION_APP, AdminColor.texto),
                    ),
                )
                Spacer(Modifier.height(16.dp))
                AdminSeccion("Administración")
                ListaAccesos(
                    listOf(
                        Triple("👤", "Usuarios y roles", Pantalla.AdminUsuarios),
                        Triple("📍", "Zonas y rutas", Pantalla.AdminZonas),
                        Triple("🚚", "Vehículos", Pantalla.AdminVehiculos),
                        Triple("🗂️", "Auditoría", Pantalla.AdminAuditoria),
                    ),
                    alNavegar,
                )
                Spacer(Modifier.height(16.dp))
                AdminBotonBorde("Cerrar sesión", AdminColor.rojo, { confirmarSalida = true })
                Spacer(Modifier.height(24.dp))
            }
        }
    }

    if (confirmarSalida) {
        AlertDialog(
            onDismissRequest = { confirmarSalida = false },
            title = { Text("¿Cerrar sesión?") },
            text = { Text("Los datos guardados en este dispositivo se conservan.") },
            confirmButton = { TextButton(onClick = { confirmarSalida = false; alCerrarSesion() }) { Text("Cerrar sesión", color = AdminColor.rojo) } },
            dismissButton = { TextButton(onClick = { confirmarSalida = false }) { Text("Cancelar") } },
        )
    }
}

@Composable
private fun ListaDatos(filas: List<Triple<String, String, Color>>) {
    pe.ecolecta.presentation.admin.design.AdminCard(padding = 0) {
        filas.forEachIndexed { i, (etiqueta, valor, color) ->
            Row(Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 14.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                AdminTexto(etiqueta, 14, AdminColor.gris)
                AdminTexto(valor, 14, color, FontWeight.SemiBold)
            }
            if (i < filas.lastIndex) Box(Modifier.fillMaxWidth().height(1.dp).background(AdminColor.crema))
        }
    }
}

@Composable
private fun ListaAccesos(accesos: List<Triple<String, String, Pantalla>>, alNavegar: (Pantalla) -> Unit) {
    pe.ecolecta.presentation.admin.design.AdminCard(padding = 0) {
        accesos.forEachIndexed { i, (icono, etiqueta, destino) ->
            Row(
                Modifier.fillMaxWidth().clickable { alNavegar(destino) }.padding(horizontal = 16.dp, vertical = 14.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                AdminTexto(icono, 18)
                AdminTexto(etiqueta, 14, peso = FontWeight.SemiBold, modifier = Modifier.weight(1f))
                Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null, tint = AdminColor.gris)
            }
            if (i < accesos.lastIndex) Box(Modifier.fillMaxWidth().height(1.dp).background(AdminColor.crema))
        }
    }
}
