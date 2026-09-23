package pe.ecolecta.presentation.admin.proveedores

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.presentation.admin.design.AdminBuscador
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminChip
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.cifra

@Composable
fun ProveedoresScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: ProveedoresViewModel = koinViewModel(),
) {
    val s by viewModel.uiState.collectAsState()
    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Proveedores", "${s.visibles.size} de ${s.filas.size} fichas", alVolver = alVolver) {
            IconButton(onClick = alCrear) { Icon(Icons.Filled.Add, contentDescription = "Nuevo proveedor", tint = AdminColor.verde) }
        }
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item { AdminBuscador(s.texto, viewModel::buscar, "Buscar por código, nombre, DNI o responsable") }
            item {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    item { AdminChip("Todas las zonas", s.zonaId == null) { viewModel.zona(null) } }
                    items(s.zonas, key = { it.id }) { z -> AdminChip(z.nombre, s.zonaId == z.id) { viewModel.zona(z.id) } }
                }
            }
            item {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    item { AdminChip("Todos", s.estado == null, AdminColor.gris) { viewModel.estado(null) } }
                    items(EstadoProveedor.entries) { e -> AdminChip(e.etiqueta(), s.estado == e, e.color()) { viewModel.estado(e) } }
                    item { AdminChip("Sin cuenta", s.sinCuenta, AdminColor.ambarTexto, s.filas.count { it.cuenta == null }) { viewModel.sinCuenta(!s.sinCuenta) } }
                }
            }
            s.error?.let { item { AdminMensaje(it, true, {}) } }
            when {
                s.cargando -> item { AdminCargando() }
                s.visibles.isEmpty() -> item { AdminVacio("Sin fichas para estos filtros", "Cambia los filtros o registra un proveedor con el botón +.") }
                else -> items(s.visibles, key = { it.proveedor.id }) { f ->
                    val p = f.proveedor
                    AdminCard(onClick = { alEditar(p.id) }, radio = 14, padding = 14) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                AdminTexto(p.nombres, 15, peso = FontWeight.Bold, maxLineas = 1)
                                AdminTexto("${p.codigo} · ${f.zona}", 12, AdminColor.gris, maxLineas = 1)
                            }
                            pe.ecolecta.presentation.admin.design.AdminEtiqueta(p.estado.etiqueta(), p.estado.color(), p.estado.color().copy(alpha = 0.12f))
                        }
                        AdminTexto(
                            "${p.tachos} tachos · ${cifra(p.capacidadTotalL)} L" + (p.dueno?.let { " · Resp. $it" } ?: ""),
                            12, AdminColor.gris, modifier = Modifier.padding(top = 6.dp), maxLineas = 1,
                        )
                        AdminTexto(
                            when {
                                f.cuenta == null -> "⚠ Sin cuenta de acceso al portal"
                                f.cuentaActiva -> "Cuenta @${f.cuenta}"
                                else -> "Cuenta @${f.cuenta} (inactiva)"
                            },
                            12, if (f.cuenta == null || !f.cuentaActiva) AdminColor.ambarTexto else AdminColor.azul, FontWeight.SemiBold,
                            Modifier.padding(top = 4.dp),
                        )
                    }
                }
            }
        }
    }
}

fun EstadoProveedor.etiqueta() = when (this) {
    EstadoProveedor.ACTIVO -> "Activo"
    EstadoProveedor.SUSPENDIDO -> "Suspendido"
    EstadoProveedor.RETIRADO -> "Retirado"
}

fun EstadoProveedor.color() = when (this) {
    EstadoProveedor.ACTIVO -> AdminColor.verde
    EstadoProveedor.SUSPENDIDO -> AdminColor.ambarTexto
    EstadoProveedor.RETIRADO -> AdminColor.rojo
}
