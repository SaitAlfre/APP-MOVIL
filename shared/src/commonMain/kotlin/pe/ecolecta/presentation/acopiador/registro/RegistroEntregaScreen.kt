package pe.ecolecta.presentation.acopiador.registro

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
import androidx.compose.material.icons.automirrored.filled.Notes
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.delay
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearLitros

private val PRESETS = listOf(10.0, 20.0, 30.0, 40.0)

@Composable
fun RegistroEntregaScreen(
    alGuardar: () -> Unit,
    alEscanearQr: () -> Unit,
    viewModel: RegistroEntregaViewModel = koinViewModel(parameters = { parametersOf(null) }),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.guardadoExitoso) {
        if (estado.guardadoExitoso) {
            if (estado.advertenciaDesviacion) delay(1400)
            alGuardar()
        }
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Registrar entrega", subtitulo = "Captura la recolección del proveedor")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            BotonSecundario("Escanear QR de proveedor", alEscanearQr, icono = Icons.Filled.QrCodeScanner)

            Text("Proveedor", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                estado.proveedores.forEach { proveedor ->
                    val seleccionado = proveedor.id == estado.proveedorId
                    Tarjeta(
                        onClick = { viewModel.onProveedorCambia(proveedor.id) },
                        modifier = if (seleccionado) {
                            Modifier.border(width = 2.dp, color = Colores.brand, shape = MaterialTheme.shapes.medium)
                        } else {
                            Modifier
                        },
                    ) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(
                                "${proveedor.codigo} - ${proveedor.nombres}",
                                style = MaterialTheme.typography.bodyLarge,
                                color = if (seleccionado) Colores.brandText else Colores.textPrimary,
                            )
                            if (seleccionado) {
                                Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(20.dp))
                            }
                        }
                        Text("${proveedor.tachos} tachos", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                    }
                }
            }

            FormularioEntrega(estado = estado, viewModel = viewModel)
        }
    }
}

/**
 * Campos de litros/tachos/observaciones + botón de guardado + diálogo de duplicado. Extraído para
 * que [RegistroEntregaScreen] (selección manual del proveedor) y la pantalla de escaneo QR
 * (proveedor ya identificado por el QR) compartan exactamente la misma lógica de registro sin
 * duplicar el ViewModel ni el caso de uso.
 */
@Composable
internal fun FormularioEntrega(estado: RegistroEntregaUiState, viewModel: RegistroEntregaViewModel) {
    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
        Text("Litros", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
        Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            PRESETS.forEach { preset ->
                BotonSecundario(formatearLitros(preset), { viewModel.aplicarPreset(preset) }, modifier = Modifier.weight(1f))
            }
        }
        CampoTexto(estado.litros, viewModel::onLitrosCambia, "Litros exactos", iconoInicial = Icons.Filled.WaterDrop)
        CampoTexto(estado.tachos, viewModel::onTachosCambia, "Tachos", iconoInicial = Icons.Filled.Inventory2)
        CampoTexto(estado.observaciones, viewModel::onObservacionesCambia, "Observaciones (opcional)", iconoInicial = Icons.AutoMirrored.Filled.Notes)

        estado.error?.let { Banner(it, TipoBanner.ERROR) }
        if (estado.guardadoExitoso && estado.advertenciaDesviacion) {
            Banner("Guardado. La cantidad se desvía bastante del promedio reciente de este proveedor.", TipoBanner.ADVERTENCIA)
        }

        BotonPrimario(
            texto = if (estado.cargando) "Registrando..." else "Registrar entrega",
            onClick = { viewModel.guardar() },
            habilitado = estado.puedeGuardar && !estado.cargando,
        )
    }

    estado.entregaDuplicada?.let {
        AlertDialog(
            onDismissRequest = viewModel::cerrarAvisoDuplicado,
            shape = MaterialTheme.shapes.large,
            title = { Text("Este proveedor ya tiene una entrega hoy") },
            text = { Text("¿Quieres sumar esta cantidad a la entrega existente o registrarla aparte?") },
            confirmButton = { TextButton(onClick = viewModel::sumarADuplicada) { Text("Sumar") } },
            dismissButton = { TextButton(onClick = viewModel::registrarAparte) { Text("Registrar aparte") } },
        )
    }
}
