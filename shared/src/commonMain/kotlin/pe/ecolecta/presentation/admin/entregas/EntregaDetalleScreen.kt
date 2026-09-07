package pe.ecolecta.presentation.admin.entregas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.RemoveCircleOutline
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun EntregaDetalleScreen(
    id: String,
    alVolver: () -> Unit = {},
    viewModel: EntregaDetalleViewModel = koinViewModel(parameters = { parametersOf(id) }),
) {
    val estado by viewModel.uiState.collectAsState()
    var mostrarCorregir by remember { mutableStateOf(false) }
    var mostrarAnular by remember { mutableStateOf(false) }

    Column(Modifier.fillMaxSize()) {
        BarraSuperior("Detalle de entrega", alVolver = alVolver)
        estado.entrega?.let { entrega ->
            Column(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.s), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
                Tarjeta {
                    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                        Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                            Text(estado.proveedorNombre, style = MaterialTheme.typography.titleLarge, color = Colores.textPrimary)
                            if (entrega.anulada) ChipEstado("ANULADA", Colores.peligro)
                        }
                        Text("Litros: ${formatearLitros(entrega.litros)}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                        Text("Tachos: ${entrega.tachos}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                        Text("Registrado: ${formatearFechaHora(entrega.registradoEn)}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                        ChipEstado(entrega.syncState.name, Colores.info)
                    }
                }
                estado.error?.let { Banner(it, TipoBanner.ERROR) }

                if (!entrega.anulada) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                        BotonPrimario(
                            "Corregir",
                            { mostrarCorregir = true },
                            icono = Icons.Filled.Edit,
                            modifier = Modifier.weight(1f),
                        )
                        BotonSecundario(
                            "Anular",
                            { mostrarAnular = true },
                            icono = Icons.Filled.RemoveCircleOutline,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }
            }

            if (mostrarCorregir) {
                DialogoCorregir(
                    litrosIniciales = entrega.litros.toString(),
                    tachosIniciales = entrega.tachos.toString(),
                    onConfirmar = { litros, tachos, motivo ->
                        viewModel.corregir(litros, tachos, motivo)
                        mostrarCorregir = false
                    },
                    onCancelar = { mostrarCorregir = false },
                )
            }
            if (mostrarAnular) {
                DialogoMotivo(
                    titulo = "Anular entrega",
                    textoConfirmar = "Anular",
                    onConfirmar = { motivo -> viewModel.anular(motivo); mostrarAnular = false },
                    onCancelar = { mostrarAnular = false },
                )
            }
        }
    }
}

@Composable
private fun DialogoCorregir(
    litrosIniciales: String,
    tachosIniciales: String,
    onConfirmar: (Double, Int, String) -> Unit,
    onCancelar: () -> Unit,
) {
    var litros by remember { mutableStateOf(litrosIniciales) }
    var tachos by remember { mutableStateOf(tachosIniciales) }
    var motivo by remember { mutableStateOf("") }
    val litrosValor = litros.toDoubleOrNull()
    val tachosValor = tachos.toIntOrNull()

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("Corregir entrega", style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                CampoTexto(litros, { litros = it }, "Litros")
                CampoTexto(tachos, { tachos = it }, "Tachos")
                CampoTexto(motivo, { motivo = it }, "Motivo (obligatorio)")
            }
        },
        confirmButton = {
            TextButton(
                onClick = { onConfirmar(litrosValor!!, tachosValor!!, motivo) },
                enabled = motivo.isNotBlank() && (litrosValor ?: 0.0) > 0.0 && (tachosValor ?: 0) > 0,
            ) { Text("Confirmar") }
        },
        dismissButton = { TextButton(onClick = onCancelar) { Text("Cancelar") } },
    )
}
