package pe.ecolecta.presentation.admin.entregas

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminChip
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.EtiquetaEntrega
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.admin.design.coloresSyncAdmin
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun EntregasScreen(
    alVerDetalle: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: EntregasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    // La lista filtrada es una propiedad calculada: se memoiza para no repetir el filtrado en cada recomposición.
    val entregasFiltradas = remember(estado.entregas, estado.filtroSyncState, estado.soloAnuladas) { estado.entregasFiltradas }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Entregas", subtitulo = "${entregasFiltradas.size} de ${estado.entregas.size} registradas", alVolver = alVolver)
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    item {
                        AdminChip("Todas", estado.filtroSyncState == null && !estado.soloAnuladas, cantidad = estado.entregas.size) {
                            viewModel.filtrarPorEstado(null)
                        }
                    }
                    items(SyncState.entries) { valor ->
                        AdminChip(
                            TextosEstado.corta(valor),
                            estado.filtroSyncState == valor,
                            coloresSyncAdmin(valor).first,
                            estado.entregas.count { !it.anulada && it.syncState == valor },
                        ) { viewModel.filtrarPorEstado(valor) }
                    }
                    item {
                        AdminChip(TextosEstado.ANULADA + "s", estado.soloAnuladas, AdminColor.rojo, estado.entregas.count { it.anulada }) {
                            viewModel.filtrarAnuladas()
                        }
                    }
                }
            }
            item { LeyendaEstados() }
            estado.error?.let { item { AdminMensaje(it, true, {}) } }
            when {
                estado.cargando -> item { AdminCargando() }
                entregasFiltradas.isEmpty() -> item {
                    AdminVacio("No hay entregas con este filtro", "Elige «Todas» u otro estado para ver más entregas.")
                }
                else -> items(entregasFiltradas, key = { it.id }) { entrega ->
                    AdminCard(onClick = { alVerDetalle(entrega.id) }, radio = 14, padding = 14) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                AdminTexto(estado.nombreProveedor(entrega.proveedorId), 15, peso = FontWeight.Bold, maxLineas = 1)
                                AdminTexto(
                                    "${formatearLitros(entrega.litros)} · ${entrega.tachos} tacho${if (entrega.tachos == 1) "" else "s"} · ${formatearFechaHora(entrega.registradoEn)}",
                                    12, AdminColor.gris, maxLineas = 1,
                                )
                            }
                            EtiquetaEntrega(entrega)
                        }
                    }
                }
            }
        }
    }
}

/** Qué significa cada estado, plegable para no ocupar la lista. */
@Composable
fun LeyendaEstados() {
    var abierta by rememberSaveable { mutableStateOf(false) }
    AdminCard(radio = 14, padding = 12, color = AdminColor.grisSuave) {
        AdminTexto(
            if (abierta) "Qué significa cada estado  ▲" else "¿Qué significa cada estado?  ▼",
            12, AdminColor.verde, FontWeight.SemiBold,
            Modifier.fillMaxWidth().clickable { abierta = !abierta },
        )
        if (abierta) {
            Column(Modifier.padding(top = 8.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                SyncState.entries.forEach { valor ->
                    val (color, fondo) = coloresSyncAdmin(valor)
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        AdminEtiqueta(TextosEstado.larga(valor), color, fondo)
                    }
                    AdminTexto(TextosEstado.ayuda(valor), 12, AdminColor.gris)
                }
                AdminEtiqueta(TextosEstado.ANULADA, AdminColor.rojo, AdminColor.rojoSuave)
                AdminTexto(TextosEstado.AYUDA_ANULADA, 12, AdminColor.gris)
                AdminTexto(
                    "Esta versión guarda los datos en el teléfono; el envío automático al servidor todavía no está activo, " +
                        "por eso las entregas nuevas o cambiadas quedan como pendientes.",
                    11, AdminColor.gris, modifier = Modifier.padding(top = 4.dp),
                )
            }
        }
    }
}
