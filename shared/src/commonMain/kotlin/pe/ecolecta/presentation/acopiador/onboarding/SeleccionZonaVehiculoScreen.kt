package pe.ecolecta.presentation.acopiador.onboarding

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Check
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.LocationOn
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
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun SeleccionZonaVehiculoScreen(
    alJornadaAbierta: () -> Unit,
    viewModel: SeleccionZonaVehiculoViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.jornadaAbierta) {
        if (estado.jornadaAbierta) alJornadaAbierta()
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Iniciar jornada", subtitulo = "Elige tu zona y vehículo de hoy")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.l)) {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                Text("Zona de recolección", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                estado.zonas.forEach { zona ->
                    FilaSeleccionable(
                        titulo = zona.nombre,
                        detalle = null,
                        icono = Icons.Filled.LocationOn,
                        seleccionado = zona.id == estado.zonaId,
                        onClick = { viewModel.seleccionarZona(zona.id) },
                    )
                }
            }

            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                Text("Vehículo", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                estado.vehiculos.forEach { vehiculo ->
                    FilaSeleccionable(
                        titulo = vehiculo.nombre,
                        detalle = "Placa ${vehiculo.placa}",
                        icono = Icons.Filled.LocalShipping,
                        seleccionado = vehiculo.id == estado.vehiculoId,
                        onClick = { viewModel.seleccionarVehiculo(vehiculo.id) },
                    )
                }
            }

            estado.ciclo?.let { ciclo ->
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    FechaDelCiclo("INICIO", ciclo.inicioLargo, Modifier.weight(1f))
                    FechaDelCiclo("FINAL", ciclo.finLargo, Modifier.weight(1f))
                }
            }

            estado.error?.let { Banner(it, TipoBanner.ERROR) }

            BotonPrimario(
                texto = if (estado.cargando) "Abriendo..." else "Abrir jornada",
                onClick = viewModel::abrirJornada,
                habilitado = estado.puedeContinuar && !estado.cargando,
            )

            Spacer(Modifier.height(Espaciado.l))
        }
    }
}

/**
 * Opción de zona o vehículo. La elegida se rellena en verde además de marcarse con el check: es la
 * primera pantalla del día y debe quedar claro de un vistazo qué se va a abrir.
 */
@Composable
private fun FilaSeleccionable(
    titulo: String,
    detalle: String?,
    icono: ImageVector,
    seleccionado: Boolean,
    onClick: () -> Unit,
) {
    Surface(
        modifier = Modifier.fillMaxWidth().clickable(onClick = onClick),
        shape = MaterialTheme.shapes.medium,
        color = if (seleccionado) Colores.brandContainer else Colores.surface,
        border = if (seleccionado) BorderStroke(1.dp, Colores.brand) else null,
        tonalElevation = 1.dp,
        shadowElevation = 1.dp,
    ) {
        Row(
            Modifier.fillMaxWidth().padding(Espaciado.m),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Row(
                Modifier.weight(1f, fill = false),
                horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(
                    icono,
                    contentDescription = null,
                    tint = if (seleccionado) Colores.brandText else Colores.textSecundario,
                    modifier = Modifier.size(20.dp),
                )
                Column {
                    Text(
                        titulo,
                        style = MaterialTheme.typography.titleMedium,
                        color = if (seleccionado) Colores.onBrandContainer else Colores.textPrimary,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                    if (detalle != null) {
                        Text(detalle, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                    }
                }
            }
            if (seleccionado) {
                Spacer(Modifier.width(Espaciado.s))
                Icon(Icons.Filled.Check, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(20.dp))
            }
        }
    }
}

@Composable
private fun FechaDelCiclo(etiqueta: String, fecha: String, modifier: Modifier = Modifier) {
    Tarjeta(modifier = modifier) {
        Text(etiqueta, style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
        Spacer(Modifier.height(Espaciado.xxs))
        Text(fecha, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
    }
}
