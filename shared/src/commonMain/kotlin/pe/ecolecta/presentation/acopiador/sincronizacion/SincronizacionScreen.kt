package pe.ecolecta.presentation.acopiador.sincronizacion

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.width
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
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.acopiador.ChipSyncAcopiador
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.PildoraPendientes
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun SincronizacionScreen(pendientesSync: Int = 0, viewModel: SincronizacionViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    val resumen = estado.resumen

    // El ViewModel vive toda la sesión (no se recrea al cambiar de pestaña): sin esto, la cola
    // mostrada se congela en el valor del primer ingreso a esta pantalla.
    LaunchedEffect(Unit) { viewModel.cargar() }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion(
            "Sincronización",
            subtitulo = "Revisa y envía tus datos pendientes",
            accion = { PildoraPendientes(pendientesSync) },
        )

        Column(
            Modifier.padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            if (estado.cuentaEnlazada) {
                Banner("Cuenta enlazada con el panel web.", TipoBanner.INFO)
            }
            Tarjeta {
                Text("Cola de sincronización", style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                Text(
                    "La sincronización se ejecuta automáticamente al tener conexión. «Sincronizar ahora» envía todo lo " +
                        "guardado en este celular, cada entrega con la cuenta de quien la registró o modificó; los contadores son solo tuyos.",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.textSecundario,
                )
                Spacer(Modifier.height(Espaciado.m))
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    MiniEstadistica("Pendientes", resumen.pendientes, if (resumen.pendientes > 0) Colores.advertencia else Colores.textPrimary, Modifier.weight(1f))
                    MiniEstadistica("Sincronizados", resumen.sincronizados, Colores.exito, Modifier.weight(1f))
                    MiniEstadistica("Rechazados", resumen.errores, if (resumen.errores > 0) Colores.peligro else Colores.textPrimary, Modifier.weight(1f))
                    MiniEstadistica("Conflictos", resumen.conflictos, if (resumen.conflictos > 0) Colores.peligro else Colores.textPrimary, Modifier.weight(1f))
                }
                Spacer(Modifier.height(Espaciado.m))
                BotonPrimario("Sincronizar ahora", viewModel::reintentar, icono = Icons.Filled.Sync)
            }

            // Sin enlace, el aviso del servidor pide justamente volver a iniciar sesión: no contradecirlo.
            if (resumen.pendientes > 0 && estado.avisoServidor == null) {
                Banner("No cierres sesión hasta sincronizar los registros pendientes.", TipoBanner.ADVERTENCIA)
            }

            estado.avisoServidor?.let { Banner(it, TipoBanner.ADVERTENCIA) }
            estado.avisoCambios?.let { Banner(it, TipoBanner.ADVERTENCIA) }
            estado.mensaje?.let { Banner(it, TipoBanner.INFO) }

            if (estado.todoSincronizado && estado.pendientes.isEmpty()) {
                EstadoVacio(
                    titulo = "Todo sincronizado",
                    descripcion = "No tienes registros pendientes de enviar.",
                    icono = Icons.Filled.CloudDone,
                )
            } else {
                Text(
                    "OPERACIONES RECIENTES",
                    style = MaterialTheme.typography.labelLarge,
                    color = Colores.textSecundario,
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
                                Modifier.weight(1f, fill = false),
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
                                        "${if (pendiente.entrega.anulada) "Anulación" else "Entrega"} · ${pendiente.nombreProveedor}",
                                        style = MaterialTheme.typography.titleMedium,
                                        color = Colores.textPrimary,
                                        maxLines = 1,
                                        overflow = TextOverflow.Ellipsis,
                                    )
                                    Text(
                                        "${formatearLitros(pendiente.entrega.litros)} · " +
                                            formatearFechaHora(pendiente.entrega.registradoEn),
                                        style = MaterialTheme.typography.bodyMedium,
                                        color = Colores.textSecundario,
                                    )
                                    // Motivo real del último intento (sin conexión, rechazo del servidor, cuenta sin enlazar).
                                    pendiente.entrega.syncError?.let { motivo ->
                                        Text(motivo, style = MaterialTheme.typography.bodySmall, color = Colores.peligro)
                                    }
                                }
                            }
                            Spacer(Modifier.width(Espaciado.s))
                            ChipSyncAcopiador(pendiente.entrega)
                        }
                    }
                }
            }

            Spacer(Modifier.height(Espaciado.l))
        }
    }
}

@Composable
private fun MiniEstadistica(etiqueta: String, valor: Int, color: Color, modifier: Modifier = Modifier) {
    Column(modifier, horizontalAlignment = Alignment.CenterHorizontally) {
        Text(valor.toString(), style = MaterialTheme.typography.headlineSmall, color = color, fontWeight = FontWeight.Bold)
        Text(etiqueta, style = MaterialTheme.typography.labelSmall, color = Colores.textSecundario, textAlign = TextAlign.Center)
    }
}
