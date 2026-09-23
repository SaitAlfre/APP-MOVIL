package pe.ecolecta.presentation.admin.conflictos

import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminTopBar
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.OrigenValorConflicto
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun ConflictosScreen(alVolver: () -> Unit = {}, viewModel: ConflictosViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    var resolucionPendiente by remember { mutableStateOf<Pair<String, OrigenValorConflicto>?>(null) }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Conflictos de sincronización", subtitulo = "${estado.conflictos.size} pendientes por resolver", alVolver = alVolver)
        estado.error?.let {
            Column(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) { Banner(it, TipoBanner.ERROR) }
        }
        if (estado.cargando) {
            IndicadorCarga()
        } else if (estado.conflictos.isEmpty()) {
            EstadoVacio(
                titulo = "No hay conflictos pendientes",
                descripcion = "Todas las entregas están sincronizadas correctamente.",
                icono = Icons.Filled.CheckCircle,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.conflictos, key = { it.id }) { entrega ->
                    Tarjeta {
                        Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                                Text(
                                    estado.nombreProveedor(entrega.proveedorId),
                                    style = MaterialTheme.typography.titleMedium,
                                    color = Colores.textPrimary,
                                    modifier = Modifier.weight(1f, fill = false),
                                )
                                ChipEstado("CONFLICTO", Colores.advertencia)
                            }
                            Text("Valor local: ${formatearLitros(entrega.litros)}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                            Text(
                                "Valor del servidor: ${entrega.litrosServidor?.let { formatearLitros(it) } ?: "—"}",
                                style = MaterialTheme.typography.bodyMedium,
                                color = Colores.textSecundario,
                            )
                            entrega.motivoConflicto?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = Colores.advertencia) }

                            Row(
                                Modifier.fillMaxWidth().padding(top = Espaciado.xs),
                                horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
                            ) {
                                BotonPrimario(
                                    "Usar local",
                                    { resolucionPendiente = entrega.id to OrigenValorConflicto.LOCAL },
                                    modifier = Modifier.weight(1f),
                                )
                                BotonSecundario(
                                    "Usar servidor",
                                    { resolucionPendiente = entrega.id to OrigenValorConflicto.SERVIDOR },
                                    modifier = Modifier.weight(1f),
                                )
                            }
                        }
                    }
                }
            }
        }
    }

    resolucionPendiente?.let { (entregaId, origen) ->
        DialogoMotivo(
            titulo = "Resolver conflicto",
            textoConfirmar = "Resolver",
            onConfirmar = { motivo -> viewModel.resolver(entregaId, origen, motivo); resolucionPendiente = null },
            onCancelar = { resolucionPendiente = null },
        )
    }
}
