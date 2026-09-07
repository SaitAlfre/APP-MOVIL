package pe.ecolecta.presentation.acopiador.home

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.WaterDrop
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
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun AcopiadorHomeScreen(
    alRegistrarEntrega: () -> Unit,
    alRegistrarLote: () -> Unit,
    alAbrirSincronizacion: () -> Unit,
    alAbrirPerfil: () -> Unit,
    viewModel: AcopiadorHomeViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    var entregaParaCorregir by remember { mutableStateOf<Entrega?>(null) }
    var entregaParaAnular by remember { mutableStateOf<Entrega?>(null) }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Hoy", subtitulo = "Resumen de tu jornada")

        Row(Modifier.fillMaxWidth().padding(horizontal = Espaciado.l), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            TarjetaEstadistica(
                etiqueta = "Litros",
                valor = formatearLitros(estado.litrosHoy),
                icono = Icons.Filled.WaterDrop,
                color = Colores.brand,
                modifier = Modifier.weight(1f),
            )
            TarjetaEstadistica(
                etiqueta = "Entregas",
                valor = estado.entregasHoy.toString(),
                icono = Icons.Filled.Inventory2,
                color = Colores.info,
                modifier = Modifier.weight(1f),
            )
            TarjetaEstadistica(
                etiqueta = "Por sincronizar",
                valor = estado.pendientesSync.toString(),
                icono = Icons.Filled.CloudUpload,
                color = if (estado.pendientesSync > 0) Colores.advertencia else Colores.exito,
                modifier = Modifier.weight(1f),
            )
        }

        Column(Modifier.fillMaxWidth().padding(Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            BotonPrimario(texto = "Registrar entrega", onClick = alRegistrarEntrega, icono = Icons.Filled.Add)
            BotonSecundario(texto = "Registrar lote", onClick = alRegistrarLote, icono = Icons.Filled.Inventory2)
        }

        EncabezadoSeccion("Últimas entregas")
        if (estado.ultimasEntregas.isEmpty()) {
            EstadoVacio(
                titulo = "Todavía no registraste entregas hoy",
                descripcion = "Tus entregas del día aparecerán aquí apenas las registres.",
                icono = Icons.Filled.Inventory2,
                textoAccion = "Registrar primera entrega",
                alPresionarAccion = alRegistrarEntrega,
            )
        } else {
            Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                estado.ultimasEntregas.forEach { entrega ->
                    Tarjeta(onClick = if (puedeEditar(entrega)) ({ entregaParaCorregir = entrega }) else null) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Column {
                                Text(estado.nombreProveedor(entrega.proveedorId), style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                                Text(
                                    "${formatearLitros(entrega.litros)} · ${formatearFechaHora(entrega.registradoEn)}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Colores.textSecundario,
                                )
                            }
                            ChipEstado(
                                if (entrega.anulada) "ANULADA" else entrega.syncState.name,
                                if (entrega.anulada) Colores.peligro else Colores.info,
                            )
                        }
                    }
                }
            }
        }
        Spacer(Modifier.height(Espaciado.l))
    }

    entregaParaCorregir?.let { entrega ->
        DialogoCorreccionRapida(
            entrega = entrega,
            onConfirmar = { litros, tachos, motivo ->
                viewModel.corregir(entrega.id, litros, tachos, motivo)
                entregaParaCorregir = null
            },
            onAnular = { entregaParaCorregir = null; entregaParaAnular = entrega },
            onCancelar = { entregaParaCorregir = null },
        )
    }

    entregaParaAnular?.let { entrega ->
        DialogoMotivo(
            titulo = "Anular entrega",
            textoConfirmar = "Anular",
            onConfirmar = { motivo -> viewModel.anular(entrega.id, motivo); entregaParaAnular = null },
            onCancelar = { entregaParaAnular = null },
        )
    }
}

private fun puedeEditar(entrega: Entrega): Boolean = !entrega.anulada && entrega.syncState != SyncState.CONFLICT

@Composable
private fun DialogoCorreccionRapida(
    entrega: Entrega,
    onConfirmar: (Double, Int, String) -> Unit,
    onAnular: () -> Unit,
    onCancelar: () -> Unit,
) {
    var litros by remember { mutableStateOf(entrega.litros.toString()) }
    var tachos by remember { mutableStateOf(entrega.tachos.toString()) }
    var motivo by remember { mutableStateOf("") }
    val litrosValor = litros.toDoubleOrNull()
    val tachosValor = tachos.toIntOrNull()

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("Corregir entrega", style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                CampoTexto(litros, { litros = it }, "Litros", iconoInicial = Icons.Filled.WaterDrop)
                CampoTexto(tachos, { tachos = it }, "Tachos", iconoInicial = Icons.Filled.Inventory2)
                CampoTexto(motivo, { motivo = it }, "Motivo (obligatorio)")
                Text(
                    "¿Te equivocaste de proveedor? Puedes anular esta entrega en su lugar.",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.textSecundario,
                )
                TextButton(onClick = onAnular) { Text("Anular esta entrega", color = Colores.peligro) }
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
