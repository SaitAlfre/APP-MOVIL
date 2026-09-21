package pe.ecolecta.presentation.acopiador.lista

import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarViewWeek
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import kotlinx.datetime.LocalDate
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.acopiador.ciclo.CicloAcopio
import pe.ecolecta.presentation.design.CampoBusqueda
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun ListaProveedoresScreen(
    alRegistrarEntrega: (String) -> Unit,
    viewModel: ListaProveedoresViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu lista…")
        return
    }

    val ciclo = estado.ciclo
    if (ciclo == null) {
        EstadoVacio(
            titulo = "No tienes una jornada abierta",
            descripcion = "Abre tu jornada para ver a los proveedores que te toca visitar hoy.",
        )
        return
    }

    when (estado.modo) {
        ModoLista.HOY -> VistaHoy(estado, ciclo, viewModel, alRegistrarEntrega)
        ModoLista.SEMANA -> VistaCiclo(estado, ciclo, viewModel)
    }
}

// ---------------------------------------------------------------------------------------------
// Hoy: a quién falta visitar
// ---------------------------------------------------------------------------------------------

@Composable
private fun VistaHoy(
    estado: ListaProveedoresUiState,
    ciclo: CicloAcopio,
    viewModel: ListaProveedoresViewModel,
    alRegistrarEntrega: (String) -> Unit,
) {
    var paraMarcarSinEntrega by remember { mutableStateOf<ProveedorDelDia?>(null) }

    Box(Modifier.fillMaxSize()) {
        Column(Modifier.fillMaxSize()) {
            EncabezadoSeccion("Lista de proveedores", subtitulo = ciclo.resumenCorto)

            Column(
                Modifier.padding(horizontal = Espaciado.l),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                TarjetaCiclo(ciclo)

                CampoBusqueda(estado.busqueda, viewModel::buscar, "Buscar proveedor o código")

                Row(
                    Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                    horizontalArrangement = Arrangement.spacedBy(Espaciado.xs),
                ) {
                    FiltroLista.entries.forEach { filtro ->
                        ChipSeleccionable(filtro.etiqueta, estado.filtro == filtro) { viewModel.filtrar(filtro) }
                    }
                }

                Text(estado.resumen, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
            }

            if (estado.visibles.isEmpty()) {
                EstadoVacio(
                    titulo = "Ningún proveedor coincide",
                    descripcion = "Prueba con otro filtro o borra la búsqueda.",
                )
            } else {
                LazyColumn(
                    Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.xs),
                    verticalArrangement = Arrangement.spacedBy(Espaciado.s),
                    contentPadding = PaddingValues(bottom = 88.dp),
                ) {
                    items(estado.visibles, key = { it.proveedor.id }) { fila ->
                        FilaProveedorDelDia(
                            fila = fila,
                            onClick = when (fila.estado) {
                                EstadoProveedorDia.POR_REGISTRAR -> ({ alRegistrarEntrega(fila.proveedor.id) })
                                EstadoProveedorDia.SIN_ENTREGA -> ({ viewModel.deshacerSinEntrega(fila.proveedor.id) })
                                else -> null
                            },
                            onMarcarSinEntrega = { paraMarcarSinEntrega = fila },
                        )
                    }
                }
            }
        }

        FloatingActionButton(
            onClick = { viewModel.cambiarModo(ModoLista.SEMANA) },
            modifier = Modifier.align(Alignment.BottomEnd).padding(Espaciado.l),
            containerColor = Colores.brand,
            contentColor = Colores.onBrand,
            shape = MaterialTheme.shapes.medium,
        ) {
            Icon(Icons.Filled.CalendarViewWeek, contentDescription = "Ver el registro del ciclo")
        }
    }

    paraMarcarSinEntrega?.let { fila ->
        DialogoSinEntrega(
            fila = fila,
            ciclo = ciclo,
            onConfirmar = { motivo ->
                viewModel.marcarSinEntrega(fila.proveedor.id, motivo)
                paraMarcarSinEntrega = null
            },
            onCancelar = { paraMarcarSinEntrega = null },
        )
    }
}

@Composable
private fun FilaProveedorDelDia(fila: ProveedorDelDia, onClick: (() -> Unit)?, onMarcarSinEntrega: () -> Unit) {
    val entrega = fila.entrega
    val detalle = when (fila.estado) {
        EstadoProveedorDia.POR_REGISTRAR -> "Pendiente de entrega"
        EstadoProveedorDia.REGISTRADO -> entrega?.let {
            "${formatearLitros(it.litros)} · ${it.tachos} tachos · ${formatearHora(it.registradoEn)}"
        }.orEmpty()
        EstadoProveedorDia.POR_SINCRONIZAR -> entrega?.let { "Registrado localmente · ${formatearLitros(it.litros)}" }.orEmpty()
        EstadoProveedorDia.SIN_ENTREGA -> listOfNotNull("Sin entrega", fila.motivoSinEntrega).joinToString(" · ")
    }
    val (etiqueta, color) = when (fila.estado) {
        EstadoProveedorDia.POR_REGISTRAR -> "REGISTRAR" to Colores.brand
        EstadoProveedorDia.REGISTRADO -> "REGISTRADO" to Colores.exito
        EstadoProveedorDia.POR_SINCRONIZAR -> "PENDIENTE" to Colores.info
        EstadoProveedorDia.SIN_ENTREGA -> "DESHACER" to Colores.advertencia
    }

    Tarjeta(onClick = onClick) {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(Modifier.weight(1f)) {
                Text(
                    "${fila.proveedor.codigo} · ${fila.proveedor.nombres}",
                    color = Colores.textPrimary,
                    style = MaterialTheme.typography.titleMedium,
                )
                Text(detalle, color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
            }
            ChipEstado(etiqueta, color, mostrarPunto = false)
        }
        if (fila.estado == EstadoProveedorDia.POR_REGISTRAR) {
            Spacer(Modifier.height(Espaciado.xs))
            Text(
                "Marcar sin entrega",
                color = Colores.advertencia,
                style = MaterialTheme.typography.labelLarge,
                modifier = Modifier
                    .clickable(onClick = onMarcarSinEntrega)
                    .padding(vertical = Espaciado.xxs),
            )
        }
    }
}

/**
 * Confirmación de "sin entrega". Insiste en que la acción es reversible porque el acopiador la usa
 * en la puerta del proveedor, con prisa y a veces antes de que la persona llegue.
 */
@Composable
private fun DialogoSinEntrega(
    fila: ProveedorDelDia,
    ciclo: CicloAcopio,
    onConfirmar: (String?) -> Unit,
    onCancelar: () -> Unit,
) {
    var motivo by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("¿Marcar como sin entrega?", style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                Text(
                    "${fila.proveedor.codigo} · ${fila.proveedor.nombres}",
                    color = Colores.textPrimary,
                    style = MaterialTheme.typography.titleMedium,
                )
                Text(
                    "Pendiente de entrega · Día ${ciclo.dia} de ${ciclo.totalDias}",
                    color = Colores.textSecundario,
                    style = MaterialTheme.typography.bodyMedium,
                )
                Text(
                    "Puedes deshacerlo si llega más tarde.",
                    color = Colores.textSecundario,
                    style = MaterialTheme.typography.bodyMedium,
                )
                CampoTexto(
                    valor = motivo,
                    onValorCambia = { motivo = it },
                    etiqueta = "Motivo (opcional)",
                )
            }
        },
        confirmButton = {
            TextButton(onClick = { onConfirmar(motivo) }) {
                Text("Marcar sin entrega", color = Colores.peligro)
            }
        },
        dismissButton = {
            TextButton(onClick = onCancelar) { Text("Cancelar") }
        },
    )
}

// ---------------------------------------------------------------------------------------------
// Ciclo: la tabla de los 6 días
// ---------------------------------------------------------------------------------------------

private val ANCHO_PROVEEDOR = 116.dp
private val ANCHO_DIA = 56.dp
private val ALTO_FILA = 48.dp

@Composable
private fun VistaCiclo(estado: ListaProveedoresUiState, ciclo: CicloAcopio, viewModel: ListaProveedoresViewModel) {
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Registro semanal", subtitulo = ciclo.resumenPago)

        Column(
            Modifier.padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            TarjetaCiclo(ciclo)

            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                ChipSeleccionable("Hoy", false) { viewModel.cambiarModo(ModoLista.HOY) }
                ChipSeleccionable("Semana", true) { }
            }

            Text(
                "Desliza para ver los ${ciclo.totalDias} días →",
                color = Colores.textSecundario,
                style = MaterialTheme.typography.bodySmall,
            )

            TablaCiclo(estado.filasCiclo, ciclo.dias)

            Text(
                "El día ${ciclo.totalDias} incluye recolección y pago.",
                color = Colores.textSecundario,
                style = MaterialTheme.typography.bodySmall,
            )

            Tarjeta {
                Row(
                    Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text("Total del ciclo registrado", color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
                    Text(
                        formatearLitros(estado.totalCicloL),
                        color = Colores.textPrimary,
                        style = MaterialTheme.typography.headlineSmall,
                    )
                }
            }

            Spacer(Modifier.height(Espaciado.l))
        }
    }
}

/**
 * La columna del proveedor queda fija y solo se desplazan los días: en una pantalla de 390px no
 * caben 6 columnas, y sin el nombre a la vista los números no significan nada.
 */
@Composable
private fun TablaCiclo(filas: List<FilaCiclo>, dias: List<LocalDate>) {
    val scroll = rememberScrollState()

    Tarjeta(padding = 0.dp) {
        Row(Modifier.fillMaxWidth()) {
            Column(Modifier.width(ANCHO_PROVEEDOR)) {
                CeldaEncabezado("PROVEEDOR", Modifier.fillMaxWidth(), alineacion = TextAlign.Start)
                filas.forEach { fila ->
                    DivisorSutil()
                    Box(Modifier.height(ALTO_FILA).padding(horizontal = Espaciado.s), contentAlignment = Alignment.CenterStart) {
                        Text(
                            fila.proveedor.nombres,
                            color = Colores.textPrimary,
                            style = MaterialTheme.typography.bodyMedium,
                            maxLines = 1,
                        )
                    }
                }
            }

            Column(Modifier.horizontalScroll(scroll)) {
                Row {
                    dias.forEach { dia ->
                        CeldaEncabezado("${dia.day.toString().padStart(2, '0')}\n${mesCorto(dia.monthNumber)}", Modifier.width(ANCHO_DIA))
                    }
                }
                filas.forEach { fila ->
                    DivisorSutil()
                    Row {
                        fila.celdas.forEach { celda -> CeldaValor(celda) }
                    }
                }
            }
        }
    }
}

@Composable
private fun CeldaEncabezado(texto: String, modifier: Modifier = Modifier, alineacion: TextAlign = TextAlign.Center) {
    Box(
        modifier.height(ALTO_FILA).padding(horizontal = Espaciado.xs),
        contentAlignment = if (alineacion == TextAlign.Start) Alignment.CenterStart else Alignment.Center,
    ) {
        Text(
            texto,
            color = Colores.textSecundario,
            style = MaterialTheme.typography.labelMedium,
            textAlign = alineacion,
        )
    }
}

@Composable
private fun CeldaValor(celda: CeldaCiclo) {
    val (texto, color) = when {
        celda.litros != null -> formatearLitros(celda.litros).removeSuffix(" L") to Colores.brandText
        celda.sinEntrega -> "SD" to Colores.advertencia
        else -> "—" to Colores.textSecundario
    }
    Box(Modifier.width(ANCHO_DIA).height(ALTO_FILA), contentAlignment = Alignment.Center) {
        Text(texto, color = color, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.SemiBold)
    }
}

// ---------------------------------------------------------------------------------------------

/** Tarjeta de contexto del ciclo, presente en las dos vistas para no perder de vista dónde se está. */
@Composable
private fun TarjetaCiclo(ciclo: CicloAcopio) {
    Tarjeta(modifier = Modifier.border(1.dp, Colores.brand.copy(alpha = 0.4f), MaterialTheme.shapes.medium)) {
        Text(ciclo.titulo, color = Colores.brandText, style = MaterialTheme.typography.labelMedium)
        Spacer(Modifier.height(Espaciado.xxs))
        Text(ciclo.detalle, color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
    }
}

private fun mesCorto(mes: Int): String = when (mes) {
    1 -> "ene"; 2 -> "feb"; 3 -> "mar"; 4 -> "abr"; 5 -> "may"; 6 -> "jun"
    7 -> "jul"; 8 -> "ago"; 9 -> "sep"; 10 -> "oct"; 11 -> "nov"; else -> "dic"
}
