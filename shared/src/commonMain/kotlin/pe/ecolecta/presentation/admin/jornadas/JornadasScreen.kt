package pe.ecolecta.presentation.admin.jornadas

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowLeft
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminChip
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminCifras
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.admin.design.fechaLarga
import pe.ecolecta.presentation.design.formatearHora

@Composable
fun JornadasScreen(
    alVerDetalle: (String) -> Unit,
    alVolver: () -> Unit,
    viewModel: JornadasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    val filas = estado.filas
    val esHoy = estado.fecha == estado.hoy

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar(
            titulo = "Jornadas",
            subtitulo = estado.fecha?.let { (if (esHoy) "Hoy · " else "") + fechaLarga(it) },
            alVolver = alVolver,
        ) {
            IconButton(onClick = { viewModel.moverDia(-1) }) {
                Icon(Icons.AutoMirrored.Filled.KeyboardArrowLeft, contentDescription = "Día anterior", tint = AdminColor.texto)
            }
            IconButton(onClick = { viewModel.moverDia(1) }, enabled = !esHoy) {
                Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = "Día siguiente", tint = if (esHoy) AdminColor.borde else AdminColor.texto)
            }
        }
        LazyRow(
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            item { AdminChip("Todas", estado.filtroZonaId == null) { viewModel.filtrarPorZona(null) } }
            items(estado.zonas, key = { it.id }) { zona ->
                AdminChip(zona.nombre, estado.filtroZonaId == zona.id) { viewModel.filtrarPorZona(zona.id) }
            }
        }
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            estado.error?.let { item { AdminMensaje(it, true, {}) } }
            if (estado.cargando) {
                item { AdminCargando() }
            } else if (filas.isEmpty()) {
                item { AdminVacio("Sin jornadas este día", "Las jornadas que abran los acopiadores aparecerán aquí con sus litros y proveedores atendidos.") }
            } else {
                item {
                    val conDatos = filas.filter { it.jornadaId != null }
                    AdminCifras(
                        listOf(
                            "Litros del día" to "${cifra(conDatos.sumOf { it.litros })} L",
                            "Abiertas" to conDatos.count { it.estado == EstadoFilaJornada.ABIERTA }.toString(),
                            "Cerradas" to conDatos.count { it.estado == EstadoFilaJornada.CERRADA }.toString(),
                        ),
                        Modifier.clip(RoundedCornerShape(14.dp)),
                        tamanoValor = 16,
                    )
                }
                items(filas, key = { it.jornadaId ?: "sin-${it.acopiador}" }) { fila -> TarjetaJornada(fila, alVerDetalle) }
            }
        }
    }
}

@Composable
private fun TarjetaJornada(fila: FilaJornada, alVerDetalle: (String) -> Unit) {
    AdminCard(onClick = fila.jornadaId?.let { id -> { alVerDetalle(id) } }) {
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f)) {
                AdminTexto(fila.ruta, 15, peso = FontWeight.Bold)
                AdminTexto(fila.acopiador, 12, AdminColor.gris)
                val horario = when {
                    fila.inicio == null -> "Aún no inicia"
                    fila.cierre != null -> "Inicio: ${formatearHora(fila.inicio)} · Cierre: ${formatearHora(fila.cierre)}"
                    else -> "Inicio: ${formatearHora(fila.inicio)}"
                }
                AdminTexto(horario + (fila.vehiculo?.let { " · $it" } ?: ""), 11, AdminColor.gris, modifier = Modifier.padding(top = 2.dp))
            }
            when (fila.estado) {
                EstadoFilaJornada.ABIERTA -> AdminEtiqueta("Abierta", AdminColor.verde, AdminColor.verdeSuave)
                EstadoFilaJornada.CERRADA -> AdminEtiqueta("Cerrada", AdminColor.gris, AdminColor.grisSuave)
                EstadoFilaJornada.NO_INICIADA -> AdminEtiqueta("No iniciada", AdminColor.ambar, AdminColor.crema)
            }
        }
        if (fila.estado != EstadoFilaJornada.NO_INICIADA) {
            Spacer(Modifier.height(8.dp))
            AdminCifras(listOf("Litros" to "${cifra(fila.litros)} L", "Proveedores" to fila.proveedores.toString()))
        }
    }
}
