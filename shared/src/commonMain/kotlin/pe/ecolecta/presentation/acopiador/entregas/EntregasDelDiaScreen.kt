package pe.ecolecta.presentation.acopiador.entregas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.etiqueta
import pe.ecolecta.presentation.acopiador.ChipSyncAcopiador
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.PildoraPendientes
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearHoraAcopio
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun EntregasDelDiaScreen(pendientesSync: Int = 0, viewModel: EntregasDelDiaViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tus entregas…")
        return
    }

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Entregas de hoy",
            subtitulo = "Total: ${formatearLitros(estado.totalLitros)}",
            accion = { PildoraPendientes(pendientesSync) },
        )

        if (!estado.jornadaAbierta && estado.entregas.isEmpty()) {
            EstadoVacio(
                titulo = "No tienes una jornada abierta",
                descripcion = "Abre tu jornada para empezar a registrar entregas.",
            )
            return
        }

        if (estado.entregas.isEmpty()) {
            EstadoVacio(
                titulo = "Todavía no registraste entregas hoy",
                descripcion = "Las entregas que registres en tu jornada aparecerán aquí.",
                icono = Icons.Filled.WaterDrop,
            )
            return
        }

        LazyColumn(
            Modifier.fillMaxSize().padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            items(estado.ordenadas, key = Entrega::id) { entrega ->
                FilaEntrega(nombre = estado.nombreProveedor(entrega.proveedorId), entrega = entrega)
            }
            item { Spacer(Modifier.height(Espaciado.l)) }
        }
    }
}

@Composable
private fun FilaEntrega(nombre: String, entrega: Entrega) {
    Tarjeta {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f, fill = false)) {
                Text(formatearHoraAcopio(entrega.registradoEn), style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                Text(nombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Text(
                    "${entrega.tachos} tachos · ${entrega.modalidad.etiqueta()}",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.textSecundario,
                )
            }
            Spacer(Modifier.width(Espaciado.s))
            Column(horizontalAlignment = Alignment.End) {
                Text(
                    formatearLitros(entrega.litros),
                    style = MaterialTheme.typography.titleLarge,
                    color = Colores.textPrimary,
                )
                Spacer(Modifier.height(2.dp))
                ChipSyncAcopiador(entrega)
            }
        }
    }
}
