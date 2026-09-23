package pe.ecolecta.presentation.admin.entregas

import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminTopBar
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Opacity
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun EntregasScreen(
    alVerDetalle: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: EntregasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    // La lista filtrada se recalcula sobre toda la lista en cada lectura (es una propiedad calculada,
    // no un StateFlow propio): se memoiza para no repetir el filtrado en cada recomposición que no
    // cambie ni la lista ni el filtro.
    val entregasFiltradas = remember(estado.entregas, estado.filtroSyncState) { estado.entregasFiltradas }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Entregas", subtitulo = "${entregasFiltradas.size} registradas", alVolver = alVolver)
        Row(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) {
            LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                item { ChipSeleccionable("Todas", estado.filtroSyncState == null) { viewModel.filtrarPorEstado(null) } }
                items(SyncState.entries) { valor ->
                    ChipSeleccionable(valor.name, estado.filtroSyncState == valor) { viewModel.filtrarPorEstado(valor) }
                }
            }
        }
        if (estado.cargando) {
            IndicadorCarga()
        } else if (estado.error != null) {
            EstadoVacio(
                titulo = "No se pudieron cargar las entregas",
                descripcion = estado.error.orEmpty(),
                icono = Icons.Filled.ErrorOutline,
            )
        } else if (entregasFiltradas.isEmpty()) {
            EstadoVacio(
                titulo = "No hay entregas que coincidan",
                descripcion = "Ajusta el filtro para ver otras entregas.",
                icono = Icons.Filled.Opacity,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(entregasFiltradas, key = { it.id }) { entrega ->
                    Tarjeta(onClick = { alVerDetalle(entrega.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f, fill = false)) {
                                Text(estado.nombreProveedor(entrega.proveedorId), style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                                Text(
                                    "${formatearLitros(entrega.litros)} · ${formatearFechaHora(entrega.registradoEn)}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Colores.textSecundario,
                                )
                            }
                            ChipEstado(
                                if (entrega.anulada) "ANULADA" else entrega.syncState.name,
                                if (entrega.anulada) Colores.peligro else colorDe(entrega.syncState),
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun colorDe(estado: SyncState) = when (estado) {
    SyncState.SYNCED -> Colores.exito
    SyncState.PENDING, SyncState.SYNCING -> Colores.info
    SyncState.ERROR -> Colores.peligro
    SyncState.CONFLICT -> Colores.advertencia
}
