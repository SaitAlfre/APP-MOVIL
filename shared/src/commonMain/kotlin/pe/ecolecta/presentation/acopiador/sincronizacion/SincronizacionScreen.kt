package pe.ecolecta.presentation.acopiador.sincronizacion

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CloudDone
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.ChipSync
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun SincronizacionScreen(viewModel: SincronizacionViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    val resumen = estado.resumen

    // El ViewModel vive toda la sesión (no se recrea al cambiar de pestaña): sin esto, la cola
    // mostrada se congela en el valor del primer ingreso a esta pantalla.
    LaunchedEffect(Unit) { viewModel.cargar() }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Cola de sincronización", subtitulo = "Revisa y envía tus datos pendientes")

        Column(
            Modifier.padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica(
                    "Pendientes",
                    resumen.pendientes.toString(),
                    colorValor = if (resumen.pendientes > 0) Colores.advertencia else Colores.textPrimary,
                    modifier = Modifier.weight(1f),
                )
                TarjetaEstadistica(
                    "Sincronizados",
                    resumen.sincronizados.toString(),
                    colorValor = Colores.exito,
                    modifier = Modifier.weight(1f),
                )
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica(
                    "Errores",
                    resumen.errores.toString(),
                    colorValor = if (resumen.errores > 0) Colores.peligro else Colores.textPrimary,
                    modifier = Modifier.weight(1f),
                )
                TarjetaEstadistica(
                    "Conflictos",
                    resumen.conflictos.toString(),
                    colorValor = if (resumen.conflictos > 0) Colores.advertencia else Colores.textPrimary,
                    modifier = Modifier.weight(1f),
                )
            }

            if (estado.todoSincronizado && estado.pendientes.isEmpty()) {
                EstadoVacio(
                    titulo = "Todo sincronizado",
                    descripcion = "No tienes registros pendientes de enviar.",
                    icono = Icons.Filled.CloudDone,
                )
            } else {
                Text(
                    "Pendientes de enviar",
                    style = MaterialTheme.typography.titleLarge,
                    color = Colores.textPrimary,
                    modifier = Modifier.padding(top = Espaciado.xs),
                )
                estado.pendientes.forEach { pendiente ->
                    Tarjeta {
                        Row(
                            Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.SpaceBetween,
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Row(
                                horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
                                verticalAlignment = Alignment.CenterVertically,
                            ) {
                                Icon(
                                    Icons.Filled.Person,
                                    contentDescription = null,
                                    tint = Colores.advertencia,
                                    modifier = Modifier.size(20.dp),
                                )
                                Column {
                                    Text(
                                        "Entrega · ${pendiente.nombreProveedor}",
                                        style = MaterialTheme.typography.titleMedium,
                                        color = Colores.textPrimary,
                                    )
                                    Text(
                                        "${formatearLitros(pendiente.entrega.litros)} · " +
                                            formatearFechaHora(pendiente.entrega.registradoEn),
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = Colores.textSecundario,
                                    )
                                }
                            }
                            ChipSync(pendiente.entrega)
                        }
                    }
                }
            }

            TarjetaSincronizacionAutomatica()

            estado.mensaje?.let { Banner(it, TipoBanner.INFO) }

            BotonPrimario("Reintentar sincronización", viewModel::reintentar, icono = Icons.Filled.Sync)

            Spacer(Modifier.height(Espaciado.l))
        }
    }
}

/**
 * Deja claro que la cola no es una tarea manual: el botón de reintentar es un empujón, no el único
 * camino. Sin esto, ver "1 pendiente" durante un rato se lee como un fallo.
 */
@Composable
private fun TarjetaSincronizacionAutomatica() {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = Colores.brandContainer,
    ) {
        Row(Modifier.padding(Espaciado.m), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            Icon(Icons.Filled.Sync, contentDescription = null, tint = Colores.brandText, modifier = Modifier.size(20.dp))
            Column {
                Text("La sincronización se ejecuta", style = MaterialTheme.typography.titleSmall, color = Colores.brandText)
                Text(
                    "automáticamente al tener conexión.",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.onBrandContainer,
                )
            }
        }
    }
}
