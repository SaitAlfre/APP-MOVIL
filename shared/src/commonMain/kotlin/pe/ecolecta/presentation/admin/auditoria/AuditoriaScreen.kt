package pe.ecolecta.presentation.admin.auditoria

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.History
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun AuditoriaScreen(viewModel: AuditoriaViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion("Auditoría", subtitulo = "Historial de acciones sobre el sistema")
        if (estado.registros.isEmpty()) {
            EstadoVacio(
                titulo = "No hay registros de auditoría todavía",
                descripcion = "Las correcciones, anulaciones y resoluciones quedarán registradas aquí.",
                icono = Icons.Filled.History,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.registros, key = { it.id }) { registro ->
                    Tarjeta {
                        Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xxs)) {
                            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                                ChipEstado(registro.accion.name, Colores.brand)
                                Text(registro.entidad, style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                            }
                            Text(formatearFechaHora(registro.ocurridoEn), style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                            registro.motivo?.let { Text(it, style = MaterialTheme.typography.bodyMedium, color = Colores.textPrimary) }
                            if (registro.valorAntes != null || registro.valorDespues != null) {
                                DivisorSutil(Modifier.padding(vertical = Espaciado.xxs))
                                Text(
                                    "${registro.valorAntes ?: "—"} → ${registro.valorDespues ?: "—"}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Colores.textSecundario,
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}
