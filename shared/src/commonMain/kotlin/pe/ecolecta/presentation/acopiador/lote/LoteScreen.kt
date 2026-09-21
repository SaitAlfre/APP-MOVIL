package pe.ecolecta.presentation.acopiador.lote

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
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Close
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
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

@Composable
fun LoteScreen(
    alGuardar: () -> Unit,
    viewModel: LoteViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    var indiceSeleccionandoProveedor by remember { mutableStateOf<Int?>(null) }

    LaunchedEffect(estado.guardadoExitoso) {
        if (estado.guardadoExitoso) {
            viewModel.confirmarNavegacion()
            alGuardar()
        }
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Registrar lote", subtitulo = "Varias entregas en una sola jornada")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            Text("Proveedores agregados", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)

            estado.filas.forEachIndexed { index, fila ->
                Tarjeta {
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Row(
                            Modifier.weight(1f).clickable { indiceSeleccionandoProveedor = index },
                            horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Icon(
                                Icons.Filled.Person,
                                contentDescription = null,
                                tint = if (fila.proveedorId.isBlank()) Colores.textSecundario else Colores.brand,
                                modifier = Modifier.size(20.dp),
                            )
                            Column {
                                Text(
                                    estado.nombreProveedor(fila.proveedorId),
                                    style = MaterialTheme.typography.titleMedium,
                                    color = if (fila.proveedorId.isBlank()) Colores.textSecundario else Colores.textPrimary,
                                )
                                val detalle = estado.detalleProveedor(fila.proveedorId)
                                if (detalle != null) {
                                    Text(detalle, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                                }
                            }
                        }
                        IconButton(onClick = { viewModel.quitarFila(index) }) {
                            Icon(Icons.Filled.Close, contentDescription = "Quitar del lote", tint = Colores.peligro)
                        }
                    }
                    Spacer(Modifier.height(Espaciado.xs))
                    Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                        CampoTexto(
                            fila.litros,
                            { viewModel.actualizarLitros(index, it) },
                            "Litros",
                            iconoInicial = Icons.Filled.WaterDrop,
                            modifier = Modifier.weight(1f),
                        )
                        CampoTexto(
                            fila.tachos,
                            { viewModel.actualizarTachos(index, it) },
                            "Tachos",
                            iconoInicial = Icons.Filled.Inventory2,
                            modifier = Modifier.weight(1f),
                        )
                    }
                }
            }

            BotonSecundario("Agregar proveedor", viewModel::agregarFila, icono = Icons.Filled.Add)

            TotalDelLote(litros = estado.litrosTotales, tachos = estado.tachosTotales)

            estado.error?.let { Banner(it, TipoBanner.ERROR) }

            BotonPrimario(
                texto = if (estado.cargando) "Guardando..." else "Guardar lote (${estado.filas.size})",
                onClick = viewModel::guardar,
                habilitado = estado.puedeGuardar && !estado.cargando,
            )

            Spacer(Modifier.height(Espaciado.l))
        }
    }

    indiceSeleccionandoProveedor?.let { index ->
        AlertDialog(
            onDismissRequest = { indiceSeleccionandoProveedor = null },
            shape = MaterialTheme.shapes.large,
            title = { Text("Elegir proveedor") },
            text = {
                Column(Modifier.verticalScroll(rememberScrollState())) {
                    estado.proveedores.forEach { proveedor ->
                        Text(
                            "${proveedor.codigo} - ${proveedor.nombres}",
                            style = MaterialTheme.typography.bodyLarge,
                            color = Colores.textPrimary,
                            modifier = Modifier
                                .fillMaxWidth()
                                .clickable {
                                    viewModel.actualizarProveedor(index, proveedor.id)
                                    indiceSeleccionandoProveedor = null
                                }
                                .padding(vertical = Espaciado.s),
                        )
                    }
                }
            },
            confirmButton = {},
            dismissButton = { TextButton(onClick = { indiceSeleccionandoProveedor = null }) { Text("Cerrar") } },
        )
    }
}

/**
 * Suma del lote antes de guardarlo. Va en verde y en grande porque es la cifra que el acopiador
 * contrasta contra lo que lleva en el vehículo; si no cuadra, el momento de corregir es este.
 */
@Composable
private fun TotalDelLote(litros: Double, tachos: Int) {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = Colores.brandContainer,
    ) {
        Column(Modifier.padding(Espaciado.m)) {
            Text("Total del lote", style = MaterialTheme.typography.bodyMedium, color = Colores.onBrandContainer)
            Spacer(Modifier.height(Espaciado.xxs))
            Text(
                "${formatearLitros(litros)} · $tachos tachos",
                style = MaterialTheme.typography.headlineSmall,
                color = Colores.onBrandContainer,
            )
        }
    }
}
