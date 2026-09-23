package pe.ecolecta.presentation.acopiador.resumen

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.LocalDrink
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
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
import androidx.compose.ui.text.style.TextOverflow
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.acopiador.home.AcopiadorHomeViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonBorde
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipSync
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Dato
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearHora
import pe.ecolecta.presentation.design.formatearLitros

/**
 * "Ver resumen": la foto completa de la jornada (litros, entregas, estado de zona/vehículo) más
 * la lista editable del día, con el mismo botón de cerrar jornada que el inicio. Comparte el
 * [AcopiadorHomeViewModel] con la pantalla de inicio (mismo ViewModelStore del rol) para no
 * repetir las consultas a jornada/entregas/proveedores.
 */
@Composable
fun ResumenJornadaScreen(viewModel: AcopiadorHomeViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    var entregaParaCorregir by remember { mutableStateOf<Entrega?>(null) }
    var entregaParaAnular by remember { mutableStateOf<Entrega?>(null) }

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando el resumen…")
        return
    }

    if (!estado.jornadaAbierta) {
        EstadoVacio(
            titulo = "No tienes una jornada abierta",
            descripcion = "Abre tu jornada desde la pestaña Jornada para ver aquí su resumen.",
        )
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        Column(
            Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.m),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica("Total litros", formatearLitros(estado.litrosHoy), color = Colores.exito, modifier = Modifier.weight(1f))
                TarjetaEstadistica("Entregas", estado.entregasHoy.toString(), modifier = Modifier.weight(1f))
            }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica("Sincronizadas", estado.sincronizadasHoy.toString(), color = Colores.exito, modifier = Modifier.weight(1f))
                TarjetaEstadistica(
                    "Pendientes",
                    estado.pendientesSync.toString(),
                    color = if (estado.pendientesSync > 0) Colores.advertencia else Colores.brand,
                    modifier = Modifier.weight(1f),
                )
            }

            Tarjeta {
                Dato("Ruta", "Ruta ${estado.zonaNombre}")
                Dato("Zona", estado.zonaNombre)
                Dato("Vehículo", estado.vehiculoInfo.ifBlank { "—" })
                Dato("Inicio", estado.horaInicio.ifBlank { "—" }, ultimo = true)
            }

            if (estado.conflictosHoy > 0) {
                Banner(
                    "${estado.conflictosHoy} entrega(s) tiene(n) un conflicto de versión. Revisa antes de cerrar la jornada.",
                    TipoBanner.ERROR,
                )
            }

            Text(
                "ENTREGAS (${estado.entregas.size})",
                style = MaterialTheme.typography.labelLarge,
                color = Colores.textSecundario,
                modifier = Modifier.padding(top = Espaciado.xs),
            )

            if (estado.entregas.isEmpty()) {
                EstadoVacio(titulo = "Todavía no registraste entregas hoy")
            } else {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    estado.ultimasEntregas.forEach { entrega ->
                        FilaEntregaEditable(
                            nombre = estado.nombreProveedor(entrega.proveedorId),
                            entrega = entrega,
                            onEditar = if (puedeEditar(entrega)) ({ entregaParaCorregir = entrega }) else null,
                        )
                    }
                }
            }

            BotonBorde(
                texto = if (estado.cerrandoJornada) "Cerrando jornada…" else "Cerrar jornada",
                color = Colores.peligro,
                icono = Icons.Filled.EventBusy,
                habilitado = !estado.cerrandoJornada,
                cargando = estado.cerrandoJornada,
                onClick = viewModel::solicitarCierreJornada,
            )
            estado.errorCierreJornada?.let { Banner(mensaje = it, tipo = TipoBanner.ERROR) }

            Spacer(Modifier.height(Espaciado.l))
        }
    }

    entregaParaCorregir?.let { entrega ->
        DialogoCorreccion(
            entrega = entrega,
            onConfirmar = { litros, tachos, motivo ->
                viewModel.corregir(entrega.id, litros, tachos, motivo)
                entregaParaCorregir = null
            },
            onAnular = { entregaParaCorregir = null; entregaParaAnular = entrega },
            onCancelar = { entregaParaCorregir = null },
        )
    }

    entregaParaAnular?.let { entrega ->
        DialogoMotivo(
            titulo = "Anular entrega",
            textoConfirmar = "Anular",
            onConfirmar = { motivo -> viewModel.anular(entrega.id, motivo); entregaParaAnular = null },
            onCancelar = { entregaParaAnular = null },
        )
    }

    if (estado.mostrarConfirmacionCierreJornada) {
        AlertDialog(
            onDismissRequest = viewModel::cancelarCierreJornada,
            shape = MaterialTheme.shapes.large,
            title = { Text("¿Cerrar la jornada?") },
            text = {
                Text(
                    "Se finalizará tu jornada de hoy y se detendrá el seguimiento de ubicación. " +
                        "Tus entregas y los pendientes por sincronizar no se pierden.",
                )
            },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreJornada) { Text("Cerrar jornada", color = Colores.peligro) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreJornada) { Text("Cancelar") } },
        )
    }
}

private fun puedeEditar(entrega: Entrega): Boolean = !entrega.anulada && entrega.syncState != SyncState.CONFLICT

@Composable
private fun FilaEntregaEditable(nombre: String, entrega: Entrega, onEditar: (() -> Unit)?) {
    Tarjeta {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f, fill = false)) {
                Text(formatearHora(entrega.registradoEn), style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                Text(nombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                    Text(formatearLitros(entrega.litros), style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                    ChipSync(entrega)
                }
            }
            if (onEditar != null) {
                IconButton(onClick = onEditar) {
                    Icon(Icons.Filled.Edit, contentDescription = "Corregir entrega de $nombre", tint = Colores.textSecundario)
                }
            }
        }
    }
}

@Composable
private fun DialogoCorreccion(
    entrega: Entrega,
    onConfirmar: (Double, Int, String) -> Unit,
    onAnular: () -> Unit,
    onCancelar: () -> Unit,
) {
    var litros by remember { mutableStateOf(entrega.litros.toString()) }
    var tachos by remember { mutableStateOf(entrega.tachos.toString()) }
    var motivo by remember { mutableStateOf("") }
    val litrosValor = litros.toDoubleOrNull()
    val tachosValor = tachos.toIntOrNull()

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("Corregir entrega", style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                CampoTexto(litros, { litros = it }, "Litros", iconoInicial = Icons.Filled.LocalDrink)
                CampoTexto(tachos, { tachos = it }, "Tachos", iconoInicial = Icons.Filled.Inventory2)
                CampoTexto(motivo, { motivo = it }, "Motivo (obligatorio)")
            }
        },
        confirmButton = {
            TextButton(
                onClick = { onConfirmar(litrosValor!!, tachosValor!!, motivo) },
                enabled = motivo.isNotBlank() && (litrosValor ?: 0.0) > 0.0 && (tachosValor ?: 0) > 0,
            ) { Text("Confirmar") }
        },
        dismissButton = { TextButton(onClick = onCancelar) { Text("Cancelar") } },
    )
}
