package pe.ecolecta.presentation.admin.usuarios

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
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.presentation.admin.design.AdminBotonChico
import pe.ecolecta.presentation.admin.design.AdminBuscador
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminChip
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio

@Composable
fun UsuariosScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: UsuariosViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    var confirmar by remember { mutableStateOf<CuentaFila?>(null) }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Usuarios y roles", "${estado.cuentas.count { it.usuario.activo }} activas de ${estado.cuentas.size}", alVolver = alVolver) {
            IconButton(onClick = alCrear) { Icon(Icons.Filled.Add, contentDescription = "Nueva cuenta", tint = AdminColor.verde) }
        }
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item { AdminBuscador(estado.texto, viewModel::buscar, "Buscar por nombre, usuario, DNI o ficha") }
            item {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    item { AdminChip("Todos", estado.rol == null, cantidad = estado.cuantos(null)) { viewModel.filtrarRol(null) } }
                    items(Rol.entries) { rol ->
                        AdminChip(rol.etiqueta, estado.rol == rol, rol.color(), estado.cuantos(rol)) { viewModel.filtrarRol(if (estado.rol == rol) null else rol) }
                    }
                }
            }
            item {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    items(FiltroEstadoCuenta.entries) { f ->
                        AdminChip(f.etiqueta, estado.estado == f, if (f == FiltroEstadoCuenta.ATENCION) AdminColor.ambarTexto else AdminColor.gris) { viewModel.filtrarEstado(f) }
                    }
                }
            }
            estado.mensaje?.let { item { AdminMensaje(it, false, viewModel::limpiarMensaje) } }
            estado.error?.let { item { AdminMensaje(it, true, viewModel::limpiarMensaje) } }
            when {
                estado.cargando -> item { AdminCargando() }
                estado.visibles.isEmpty() -> item {
                    AdminVacio("Sin cuentas para estos filtros", "Cambia la búsqueda o crea una cuenta nueva con el botón +.")
                }
                else -> items(estado.visibles, key = { it.usuario.id }) { fila ->
                    TarjetaCuenta(
                        fila = fila,
                        procesando = estado.procesando == fila.usuario.id,
                        alEditar = { alEditar(fila.usuario.id) },
                        alAlternar = { confirmar = fila },
                        alDesbloquear = { viewModel.desbloquear(fila) },
                    )
                }
            }
        }
    }

    confirmar?.let { fila ->
        val desactivar = fila.usuario.activo
        AlertDialog(
            onDismissRequest = { confirmar = null },
            title = { Text(if (desactivar) "¿Desactivar cuenta?" else "¿Reactivar cuenta?") },
            text = {
                Text(
                    if (desactivar) "${fila.usuario.nombres} ya no podrá iniciar sesión. Sus registros se conservan y puedes reactivarla cuando quieras."
                    else "${fila.usuario.nombres} podrá volver a iniciar sesión con su PIN.",
                )
            },
            confirmButton = {
                TextButton(onClick = { viewModel.alternarEstado(fila); confirmar = null }) {
                    Text(if (desactivar) "Desactivar" else "Reactivar", color = if (desactivar) AdminColor.rojo else AdminColor.verde)
                }
            },
            dismissButton = { TextButton(onClick = { confirmar = null }) { Text("Cancelar") } },
        )
    }
}

@Composable
private fun TarjetaCuenta(fila: CuentaFila, procesando: Boolean, alEditar: () -> Unit, alAlternar: () -> Unit, alDesbloquear: () -> Unit) {
    val u = fila.usuario
    AdminCard(onClick = alEditar, radio = 14, padding = 14) {
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            AvatarCuenta(u.nombres, u.roles, u.activo)
            Column(Modifier.weight(1f)) {
                AdminTexto(u.nombres, 15, if (u.activo) AdminColor.texto else AdminColor.gris, FontWeight.Bold, maxLineas = 1)
                AdminTexto("@${u.username} · DNI ${u.dni}", 12, AdminColor.gris, maxLineas = 1)
            }
            when {
                !u.activo -> AdminEtiqueta("Inactiva", AdminColor.gris, AdminColor.grisSuave)
                fila.bloqueada -> AdminEtiqueta("Bloqueada", AdminColor.rojo, AdminColor.rojoSuave)
                else -> AdminEtiqueta("Activa", AdminColor.verde, AdminColor.verdeSuave)
            }
        }
        Spacer(Modifier.height(10.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(6.dp)) { u.roles.sortedBy { it.ordinal }.forEach { EtiquetaRol(it) } }
        val detalle = listOfNotNull(fila.zona?.let { "Zona: $it" }, fila.ficha?.let { "Ficha: $it" })
        if (detalle.isNotEmpty()) AdminTexto(detalle.joinToString(" · "), 12, AdminColor.gris, modifier = Modifier.padding(top = 8.dp), maxLineas = 2)
        fila.pendiente?.let { AdminTexto("⚠ $it", 12, AdminColor.ambarTexto, FontWeight.SemiBold, modifier = Modifier.padding(top = 6.dp)) }
        Spacer(Modifier.height(10.dp))
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            AdminBotonChico("Editar", AdminColor.blanco, AdminColor.verdeOscuro, alEditar, !procesando)
            if (fila.bloqueada && u.activo) AdminBotonChico("Desbloquear", AdminColor.rojo, AdminColor.rojoSuave, alDesbloquear, !procesando)
            AdminBotonChico(
                if (procesando) "Procesando…" else if (u.activo) "Desactivar" else "Reactivar",
                if (u.activo) AdminColor.gris else AdminColor.verde,
                if (u.activo) AdminColor.crema else AdminColor.verdeSuave,
                alAlternar, !procesando,
            )
        }
    }
}
