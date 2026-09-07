package pe.ecolecta.presentation.admin.traslados

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.SwapHoriz
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
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonAccion
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun TrasladosScreen(viewModel: TrasladosViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    var idParaRechazar by remember { mutableStateOf<String?>(null) }

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Traslados",
            subtitulo = "Cambios de zona solicitados por proveedores",
            accion = { BotonAccion("Nuevo", { viewModel.mostrarDialogoCrear(true) }, icono = Icons.Filled.Add) },
        )
        estado.error?.let {
            Column(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) { Banner(it, TipoBanner.ERROR) }
        }
        if (estado.traslados.isEmpty()) {
            EstadoVacio(
                titulo = "No hay traslados registrados",
                descripcion = "Los cambios de zona de proveedores aparecerán aquí.",
                icono = Icons.Filled.SwapHoriz,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.traslados, key = { it.id }) { traslado ->
                    Tarjeta {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(
                                estado.nombreProveedor(traslado.proveedorId),
                                style = MaterialTheme.typography.titleMedium,
                                color = Colores.textPrimary,
                                modifier = Modifier.weight(1f, fill = false),
                            )
                            ChipEstado(
                                traslado.estado.name,
                                when (traslado.estado) {
                                    EstadoTraslado.PENDIENTE -> Colores.advertencia
                                    EstadoTraslado.AUTORIZADO -> Colores.exito
                                    EstadoTraslado.RECHAZADO -> Colores.peligro
                                },
                            )
                        }
                        Text(
                            "${estado.nombreZona(traslado.zonaOrigenId)} → ${estado.nombreZona(traslado.zonaDestinoId)}",
                            style = MaterialTheme.typography.bodyMedium,
                            color = Colores.textSecundario,
                        )
                        traslado.motivo?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario) }

                        if (traslado.estado == EstadoTraslado.PENDIENTE) {
                            Row(Modifier.padding(top = Espaciado.s), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                                BotonPrimario("Autorizar", { viewModel.autorizar(traslado.id) }, modifier = Modifier.weight(1f))
                                BotonSecundario("Rechazar", { idParaRechazar = traslado.id }, modifier = Modifier.weight(1f))
                            }
                        }
                    }
                }
            }
        }
    }

    if (estado.mostrarDialogoCrear) {
        DialogoCrearTraslado(estado, onCrear = viewModel::crear, onCancelar = { viewModel.mostrarDialogoCrear(false) })
    }

    idParaRechazar?.let { id ->
        DialogoMotivo(
            titulo = "Rechazar traslado",
            textoConfirmar = "Rechazar",
            onConfirmar = { motivo -> viewModel.rechazar(id, motivo); idParaRechazar = null },
            onCancelar = { idParaRechazar = null },
        )
    }
}

@Composable
private fun DialogoCrearTraslado(estado: TrasladosUiState, onCrear: (String, String, String) -> Unit, onCancelar: () -> Unit) {
    var proveedorId by remember { mutableStateOf(estado.proveedores.firstOrNull()?.id.orEmpty()) }
    var zonaDestinoId by remember { mutableStateOf(estado.zonas.firstOrNull()?.id.orEmpty()) }
    var motivo by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("Nuevo traslado", style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                Text("Proveedor", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                    items(estado.proveedores, key = { it.id }) { proveedor ->
                        ChipSeleccionable("${proveedor.codigo} - ${proveedor.nombres}", proveedor.id == proveedorId) { proveedorId = proveedor.id }
                    }
                }
                Text("Zona destino", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                    items(estado.zonas, key = { it.id }) { zona ->
                        ChipSeleccionable(zona.nombre, zona.id == zonaDestinoId) { zonaDestinoId = zona.id }
                    }
                }
                CampoTexto(motivo, { motivo = it }, "Motivo")
            }
        },
        confirmButton = {
            TextButton(
                onClick = { onCrear(proveedorId, zonaDestinoId, motivo) },
                enabled = proveedorId.isNotBlank() && zonaDestinoId.isNotBlank(),
            ) { Text("Crear") }
        },
        dismissButton = { TextButton(onClick = onCancelar) { Text("Cancelar") } },
    )
}
