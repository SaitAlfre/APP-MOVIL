package pe.ecolecta.presentation.admin.vehiculos

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminCampo
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminInterruptor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio

@Composable
fun VehiculosScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: VehiculosViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Vehículos", "${estado.vehiculos.count { it.enRuta != null }} en ruta · ${estado.vehiculos.count { it.vehiculo.activo }} activos", alVolver = alVolver) {
            IconButton(onClick = alCrear) { Icon(Icons.Filled.Add, contentDescription = "Nuevo vehículo", tint = AdminColor.verde) }
        }
        LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            estado.error?.let { item { AdminMensaje(it, true, {}) } }
            when {
                estado.cargando -> item { AdminCargando() }
                estado.vehiculos.isEmpty() -> item { AdminVacio("No hay vehículos", "Registra los camiones que usan los acopiadores al abrir su jornada.") }
                else -> items(estado.vehiculos, key = { it.vehiculo.id }) { fila ->
                    val v = fila.vehiculo
                    AdminCard(onClick = { alEditar(v.id) }, radio = 14, padding = 14) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminTexto("🚚", 22, modifier = Modifier.padding(end = 12.dp))
                            Column(Modifier.weight(1f)) {
                                AdminTexto(v.nombre, 15, if (v.activo) AdminColor.texto else AdminColor.gris, FontWeight.Bold)
                                AdminTexto("Placa ${v.placa} · ${fila.jornadas} jornadas", 12, AdminColor.gris)
                            }
                            when {
                                !v.activo -> AdminEtiqueta("Inactivo", AdminColor.gris, AdminColor.grisSuave)
                                fila.enRuta != null -> AdminEtiqueta("En ruta", AdminColor.azul, AdminColor.azulSuave)
                                else -> AdminEtiqueta("Disponible", AdminColor.verde, AdminColor.verdeSuave)
                            }
                        }
                        fila.enRuta?.let { AdminTexto(it, 12, AdminColor.azul, FontWeight.SemiBold, Modifier.padding(top = 6.dp)) }
                    }
                }
            }
        }
    }
}

@Composable
fun VehiculoFormScreen(
    id: String?,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    viewModel: VehiculoFormViewModel = koinViewModel(key = id ?: "nuevo", parameters = { parametersOf(id) }),
) {
    val s by viewModel.uiState.collectAsState()
    LaunchedEffect(s.guardado) { if (s.guardado) alGuardar() }
    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar(if (s.esEdicion) "Editar vehículo" else "Nuevo vehículo", "Vehículos", alVolver = alVolver)
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    AdminCampo(s.nombre, viewModel::nombre, "Nombre", marcador = "Ej: Camión 5")
                    AdminCampo(s.placa, viewModel::placa, "Placa", marcador = "Ej: V5E-345", ayuda = "La placa no puede repetirse.")
                    if (s.esEdicion) {
                        AdminInterruptor("Vehículo activo", "No se puede desactivar mientras esté en una jornada abierta.", s.activo, viewModel::activo)
                    }
                }
            }
            s.error?.let { AdminMensaje(it, true, {}) }
            AdminBoton(if (s.guardando) "Guardando…" else if (s.esEdicion) "Guardar cambios" else "Registrar vehículo", viewModel::guardar, habilitado = !s.guardando)
        }
    }
}
