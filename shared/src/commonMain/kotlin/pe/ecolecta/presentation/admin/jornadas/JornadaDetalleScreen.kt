package pe.ecolecta.presentation.admin.jornadas

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
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Opacity
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun JornadaDetalleScreen(
    id: String,
    alVerEntrega: (String) -> Unit,
    alVolver: () -> Unit = {},
    viewModel: JornadaDetalleViewModel = koinViewModel(key = id, parameters = { parametersOf(id) }),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Detalle de jornada", alVolver = alVolver)
        estado.jornada?.let { jornada ->
            Tarjeta(modifier = Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.s)) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f, fill = false)) {
                        Text("Fecha: ${jornada.fecha}", style = MaterialTheme.typography.bodyMedium, color = Colores.textPrimary)
                        Text(
                            "${estado.entregas.size} entregas registradas",
                            style = MaterialTheme.typography.bodySmall,
                            color = Colores.textSecundario,
                        )
                    }
                    ChipEstado(
                        if (jornada.estaAbierta) "ABIERTA" else "CERRADA",
                        if (jornada.estaAbierta) Colores.exito else Colores.textSecundario,
                    )
                }
            }
        }
        if (estado.error != null) {
            EstadoVacio(
                titulo = "No se pudo cargar la jornada",
                descripcion = estado.error.orEmpty(),
                icono = Icons.Filled.ErrorOutline,
            )
        } else if (estado.entregas.isEmpty()) {
            EstadoVacio(
                titulo = "Sin entregas todavía",
                descripcion = "Esta jornada no tiene entregas registradas.",
                icono = Icons.Filled.Opacity,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.s),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.entregas, key = { it.id }) { entrega ->
                    Tarjeta(onClick = { alVerEntrega(entrega.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f, fill = false)) {
                                Text(estado.nombreProveedor(entrega.proveedorId), style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                                Text(formatearLitros(entrega.litros), style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                            }
                            ChipEstado(entrega.syncState.name, Colores.info)
                        }
                    }
                }
            }
        }
    }
}
