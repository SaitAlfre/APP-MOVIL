package pe.ecolecta.presentation.proveedor.entregas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.EnlaceTexto
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun MisEntregasScreen(
    alVerDetalle: (String) -> Unit,
    viewModel: MisEntregasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion("Mis entregas")

        Row(
            Modifier.horizontalScroll(rememberScrollState()).padding(horizontal = Espaciado.l),
            horizontalArrangement = Arrangement.spacedBy(Espaciado.xs),
        ) {
            RangoResumen.entries.forEach { rango ->
                ChipSeleccionable(rango.etiqueta, estado.rangoResumen == rango) { viewModel.cambiarRangoResumen(rango) }
            }
        }

        estado.resumen?.let { resumen ->
            Tarjeta(modifier = Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    DatoResumen("Entregas", resumen.numeroEntregas.toString())
                    DatoResumen("Litros", formatearLitros(resumen.litrosTotales))
                    DatoResumen("Promedio", formatearLitros(resumen.promedioPorEntrega))
                }
            }
        }

        Row(
            Modifier.horizontalScroll(rememberScrollState()).padding(horizontal = Espaciado.l, vertical = Espaciado.xxs),
            horizontalArrangement = Arrangement.spacedBy(Espaciado.xs),
        ) {
            FiltroRangoFecha.entries.forEach { rango ->
                ChipSeleccionable(rango.etiqueta, estado.filtroRango == rango) { viewModel.aplicarFiltro(rango, estado.filtroEstado) }
            }
        }
        Row(
            Modifier.horizontalScroll(rememberScrollState()).padding(horizontal = Espaciado.l, vertical = Espaciado.xxs),
            horizontalArrangement = Arrangement.spacedBy(Espaciado.xs),
        ) {
            ChipSeleccionable("Todos los estados", estado.filtroEstado == null) { viewModel.aplicarFiltro(estado.filtroRango, null) }
            SyncState.entries.forEach { valor ->
                ChipSeleccionable(valor.name, estado.filtroEstado == valor) { viewModel.aplicarFiltro(estado.filtroRango, valor) }
            }
        }

        if (estado.entregas.isEmpty() && !estado.cargando) {
            EstadoVacio(
                titulo = "No hay entregas para este filtro",
                descripcion = "Prueba con otro rango de fecha o estado de sincronización.",
            )
        } else if (estado.cargando) {
            IndicadorCarga()
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l),
                verticalArrangement = Arrangement.spacedBy(Espaciado.xs),
            ) {
                items(estado.entregas, key = { it.id }) { entrega ->
                    Tarjeta(onClick = { alVerDetalle(entrega.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Column {
                                Text(formatearLitros(entrega.litros), color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
                                Text(formatearFechaHora(entrega.registradoEn), color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
                            }
                            ChipEstado(
                                if (entrega.anulada) "ANULADA" else entrega.syncState.name,
                                if (entrega.anulada) Colores.peligro else Colores.info,
                            )
                        }
                    }
                }
                if (estado.usaPaginacion && estado.hayMasPaginas) {
                    item {
                        Box(Modifier.fillMaxWidth().padding(vertical = Espaciado.s), contentAlignment = Alignment.Center) {
                            if (estado.cargandoMas) {
                                IndicadorCarga(modifier = Modifier.height(48.dp))
                            } else {
                                EnlaceTexto(texto = "Cargar más", onClick = viewModel::cargarMas)
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun DatoResumen(etiqueta: String, valor: String) {
    Column {
        Text(etiqueta, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
        Text(valor, color = Colores.textPrimary, style = MaterialTheme.typography.titleSmall)
    }
}
