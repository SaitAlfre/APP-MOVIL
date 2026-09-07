package pe.ecolecta.presentation.admin.jornadas

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
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta

@Composable
fun JornadasScreen(
    alVerDetalle: (String) -> Unit,
    viewModel: JornadasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion("Jornadas", subtitulo = "${estado.jornadasFiltradas.size} jornadas")
        Row(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) {
            LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                item { ChipSeleccionable("Todas", estado.filtroZonaId == null) { viewModel.filtrarPorZona(null) } }
                items(estado.zonas, key = { it.id }) { zona ->
                    ChipSeleccionable(zona.nombre, estado.filtroZonaId == zona.id) { viewModel.filtrarPorZona(zona.id) }
                }
            }
        }
        if (estado.jornadasFiltradas.isEmpty()) {
            EstadoVacio(
                titulo = "No hay jornadas registradas",
                descripcion = "Las jornadas abiertas por ACOPIADOR aparecerán aquí.",
                icono = Icons.Filled.CalendarMonth,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.jornadasFiltradas, key = { it.id }) { jornada ->
                    Tarjeta(onClick = { alVerDetalle(jornada.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f, fill = false)) {
                                Text(estado.nombreUsuario(jornada.usuarioId), style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                                Text(
                                    "${estado.nombreZona(jornada.zonaId)} · ${estado.nombreVehiculo(jornada.vehiculoId)} · ${jornada.fecha}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Colores.textSecundario,
                                )
                            }
                            ChipEstado(
                                if (jornada.estaAbierta) "ABIERTA" else "CERRADA",
                                if (jornada.estaAbierta) Colores.exito else Colores.textSecundario,
                            )
                        }
                    }
                }
            }
        }
    }
}
