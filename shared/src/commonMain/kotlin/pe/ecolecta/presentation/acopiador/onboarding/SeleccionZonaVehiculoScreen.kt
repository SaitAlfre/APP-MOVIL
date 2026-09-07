package pe.ecolecta.presentation.acopiador.onboarding

import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import androidx.compose.ui.graphics.vector.ImageVector

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
                Text("Zona", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                estado.zonas.forEach { zona ->
                    FilaSeleccionable(zona.nombre, Icons.Filled.LocationOn, zona.id == estado.zonaId) { viewModel.seleccionarZona(zona.id) }
                }
            }

            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                Text("Vehículo", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                estado.vehiculos.forEach { vehiculo ->
                    FilaSeleccionable("${vehiculo.nombre} (${vehiculo.placa})", Icons.Filled.LocalShipping, vehiculo.id == estado.vehiculoId) {
                        viewModel.seleccionarVehiculo(vehiculo.id)
                    }
                }
            }

            estado.error?.let { Banner(it, TipoBanner.ERROR) }

            BotonPrimario(
                texto = if (estado.cargando) "Abriendo..." else "Abrir jornada",
                onClick = viewModel::abrirJornada,
                habilitado = estado.puedeContinuar && !estado.cargando,
            )
        }
    }
}

@Composable
private fun FilaSeleccionable(texto: String, icono: ImageVector, seleccionado: Boolean, onClick: () -> Unit) {
    Tarjeta(
        onClick = onClick,
        modifier = if (seleccionado) Modifier.border(width = 2.dp, color = Colores.brand, shape = MaterialTheme.shapes.medium) else Modifier,
    ) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                Icon(icono, contentDescription = null, tint = if (seleccionado) Colores.brand else Colores.textSecundario, modifier = Modifier.size(20.dp))
                Text(
                    texto,
                    style = MaterialTheme.typography.bodyLarge,
                    color = if (seleccionado) Colores.brandText else Colores.textPrimary,
                )
            }
            if (seleccionado) {
                Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(20.dp))
            }
        }
    }
}
