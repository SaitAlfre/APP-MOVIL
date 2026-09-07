package pe.ecolecta.presentation.proveedor.detalle

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun DetalleEntregaProveedorScreen(
    id: String,
    viewModel: DetalleEntregaProveedorViewModel = koinViewModel(parameters = { parametersOf(id) }),
) {
    val estado by viewModel.uiState.collectAsState()

    when {
        estado.cargando -> IndicadorCarga(mensaje = "Cargando entrega…")
        estado.noEncontrada -> EstadoVacio(titulo = "No se encontró esta entrega")
        else -> estado.entrega?.let { entrega ->
            Column(
                Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(Espaciado.l),
                verticalArrangement = Arrangement.spacedBy(Espaciado.m),
            ) {
                if (entrega.anulada) {
                    Banner(mensaje = "Esta entrega fue anulada por el administrador.", tipo = TipoBanner.ERROR)
                }
                if (entrega.motivoConflicto != null) {
                    Banner(
                        mensaje = "Discrepancia con el servidor: ${entrega.motivoConflicto}. " +
                            (entrega.litrosServidor?.let { "Valor del servidor: ${formatearLitros(it)}." } ?: ""),
                        tipo = TipoBanner.ADVERTENCIA,
                    )
                }

                Tarjeta {
                    Text("LITROS ENTREGADOS", color = Colores.textSecundario, style = MaterialTheme.typography.labelMedium)
                    Text(formatearLitros(entrega.litros), color = Colores.textPrimary, style = MaterialTheme.typography.headlineMedium)
                    Text(formatearFechaHora(entrega.registradoEn), color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
                }

                Tarjeta {
                    Dato("Tachos", entrega.tachos.toString())
                    Dato("Acopiador", estado.nombreAcopiador)
                    Dato("Zona", estado.nombreZona)
                    Dato("Vehículo", estado.nombreVehiculo)
                    Dato("Lote", entrega.loteId ?: "—")
                    Dato("Observaciones", entrega.observaciones ?: "—", ultimo = true)
                }

                Tarjeta {
                    Text("Estado de sincronización", color = Colores.textSecundario, style = MaterialTheme.typography.labelMedium)
                    Row(Modifier.fillMaxWidth().padding(top = Espaciado.xxs)) {
                        ChipEstado(textoEstadoSync(entrega.syncState), colorEstadoSync(entrega.syncState))
                    }
                }
            }
        }
    }
}

private fun textoEstadoSync(estado: SyncState): String = when (estado) {
    SyncState.PENDING -> "Pendiente de sincronización"
    SyncState.SYNCING -> "Sincronizando…"
    SyncState.SYNCED -> "Sincronizado"
    SyncState.ERROR -> "Error de sincronización"
    SyncState.CONFLICT -> "Requiere revisión"
}

@Composable
private fun colorEstadoSync(estado: SyncState) = when (estado) {
    SyncState.SYNCED -> Colores.exito
    SyncState.PENDING, SyncState.SYNCING -> Colores.info
    SyncState.ERROR -> Colores.peligro
    SyncState.CONFLICT -> Colores.advertencia
}

@Composable
private fun Dato(etiqueta: String, valor: String, ultimo: Boolean = false) {
    Column(Modifier.padding(bottom = if (ultimo) 0.dp else Espaciado.s)) {
        Text(etiqueta, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
        Text(valor, color = Colores.textPrimary, style = MaterialTheme.typography.bodyLarge)
    }
}
