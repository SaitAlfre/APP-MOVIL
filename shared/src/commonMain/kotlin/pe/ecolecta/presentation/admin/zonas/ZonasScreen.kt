package pe.ecolecta.presentation.admin.zonas

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
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
import pe.ecolecta.presentation.admin.design.AdminCifras
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminInterruptor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio

@Composable
fun ZonasScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: ZonasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Zonas y rutas", "${estado.zonas.count { it.zona.activo }} activas", alVolver = alVolver) {
            IconButton(onClick = alCrear) { Icon(Icons.Filled.Add, contentDescription = "Nueva zona", tint = AdminColor.verde) }
        }
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            estado.error?.let { item { AdminMensaje(it, true, {}) } }
            when {
                estado.cargando -> item { AdminCargando() }
                estado.zonas.isEmpty() -> item { AdminVacio("No hay zonas", "Crea la primera ruta para asignar proveedores, acopiadores y técnicos.") }
                else -> items(estado.zonas, key = { it.zona.id }) { fila ->
                    AdminCard(onClick = { alEditar(fila.zona.id) }) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminTexto(fila.zona.nombre, 15, if (fila.zona.activo) AdminColor.texto else AdminColor.gris, FontWeight.Bold, Modifier.weight(1f))
                            when {
                                !fila.zona.activo -> AdminEtiqueta("Inactiva", AdminColor.gris, AdminColor.grisSuave)
                                fila.jornadaAbiertaPor != null -> AdminEtiqueta("Jornada abierta", AdminColor.verde, AdminColor.verdeSuave)
                                else -> AdminEtiqueta("Activa", AdminColor.verde, AdminColor.verdeSuave)
                            }
                        }
                        Spacer(Modifier.height(10.dp))
                        AdminCifras(
                            listOf(
                                "Proveedores" to fila.proveedoresActivos.toString(),
                                "Acopiadores" to fila.acopiadores.size.toString(),
                                "Técnicos" to fila.tecnicos.size.toString(),
                            ),
                            tamanoValor = 16,
                        )
                        val personal = fila.acopiadores + fila.tecnicos
                        if (personal.isNotEmpty()) AdminTexto("Asignados: ${personal.joinToString()}", 12, AdminColor.gris, modifier = Modifier.padding(top = 8.dp), maxLineas = 2)
                        fila.jornadaAbiertaPor?.let { AdminTexto("En ruta ahora: $it", 12, AdminColor.verde, FontWeight.SemiBold, Modifier.padding(top = 4.dp)) }
                    }
                }
            }
        }
    }
}

@Composable
fun ZonaFormScreen(
    id: String?,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    viewModel: ZonaFormViewModel = koinViewModel(key = id ?: "nuevo", parameters = { parametersOf(id) }),
) {
    val s by viewModel.uiState.collectAsState()
    LaunchedEffect(s.guardado) { if (s.guardado) alGuardar() }
    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar(if (s.esEdicion) "Editar zona" else "Nueva zona", "Zonas y rutas", alVolver = alVolver)
        Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            AdminCard {
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    AdminCampo(s.nombre, viewModel::nombre, "Nombre de la zona", marcador = "Ej: COLLANA I-YASIN-HUAN")
                    if (s.esEdicion) {
                        AdminInterruptor(
                            "Zona activa",
                            "Solo puede desactivarse sin proveedores activos, sin personal asignado y sin jornada abierta.",
                            s.activo, viewModel::activo,
                        )
                    }
                }
            }
            s.error?.let { AdminMensaje(it, true, {}) }
            AdminBoton(if (s.guardando) "Guardando…" else if (s.esEdicion) "Guardar cambios" else "Crear zona", viewModel::guardar, habilitado = !s.guardando)
        }
    }
}
