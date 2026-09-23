package pe.ecolecta.presentation.admin.alertas

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
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.AlertaAdmin
import pe.ecolecta.domain.model.TipoAlertaAdmin
import pe.ecolecta.presentation.admin.design.AdminBotonChico
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminChip
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.haceTiempo
import pe.ecolecta.presentation.navegacion.Pantalla
import kotlin.time.Clock

fun TipoAlertaAdmin.color(): Color = when (this) {
    TipoAlertaAdmin.CONFLICTO -> AdminColor.rojo
    TipoAlertaAdmin.RECLAMO -> AdminColor.ambar
    TipoAlertaAdmin.TRASLADO -> AdminColor.azul
    TipoAlertaAdmin.CALIDAD -> AdminColor.morado
}

fun TipoAlertaAdmin.icono(): String = when (this) {
    TipoAlertaAdmin.CONFLICTO -> "⚡"
    TipoAlertaAdmin.RECLAMO -> "📝"
    TipoAlertaAdmin.TRASLADO -> "🚛"
    TipoAlertaAdmin.CALIDAD -> "🔬"
}

fun TipoAlertaAdmin.etiqueta(): String = when (this) {
    TipoAlertaAdmin.CONFLICTO -> "Conflicto"
    TipoAlertaAdmin.RECLAMO -> "Reclamo"
    TipoAlertaAdmin.TRASLADO -> "Traslado"
    TipoAlertaAdmin.CALIDAD -> "Calidad"
}

fun TipoAlertaAdmin.accion(): String = when (this) {
    TipoAlertaAdmin.CONFLICTO, TipoAlertaAdmin.CALIDAD -> "Revisar"
    TipoAlertaAdmin.RECLAMO -> "Resolver"
    TipoAlertaAdmin.TRASLADO -> "Aprobar"
}

/** Las decisiones sobre una alerta: la acción principal (✓) y descartar (✕). */
class AccionesAlerta(val principal: (AlertaAdmin) -> Unit, val descartar: (AlertaAdmin) -> Unit)

private sealed interface Dialogo {
    data class Reclamo(val alerta: AlertaAdmin) : Dialogo
    data class AprobarTraslado(val alerta: AlertaAdmin) : Dialogo
    data class Rechazar(val alerta: AlertaAdmin) : Dialogo
}

/**
 * Emite los diálogos de confirmación y devuelve las acciones listas para conectar a las
 * tarjetas. Conflictos y calidad se revisan en su módulo; el resto se decide aquí.
 */
@Composable
fun recordarAccionesAlerta(viewModel: AdminAlertasViewModel, estado: AdminAlertasUiState, alNavegar: (Pantalla) -> Unit): AccionesAlerta {
    var dialogo by remember { mutableStateOf<Dialogo?>(null) }

    when (val d = dialogo) {
        is Dialogo.Reclamo -> {
            val solicitud = estado.solicitudDe(d.alerta)
            AlertDialog(
                onDismissRequest = { dialogo = null },
                title = { Text("Reclamo del proveedor") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        AdminTexto(d.alerta.descripcion, 14, peso = FontWeight.SemiBold)
                        solicitud?.let { s ->
                            AdminTexto(s.descripcion, 13, AdminColor.gris)
                            s.litros?.let { AdminTexto("Litros solicitados: $it L", 13) }
                            AdminTexto(if (s.referenciaId != null) "Entrega referida: ${s.referenciaId.take(8)}…" else "Entrega no registrada", 12, AdminColor.gris)
                            if (s.referenciaId != null) {
                                AdminTexto(
                                    "Abrir la entrega para corregir los litros →", 13, AdminColor.verde, FontWeight.Bold,
                                    Modifier.clickable { dialogo = null; alNavegar(Pantalla.AdminEntregaDetalle(s.referenciaId)) },
                                )
                            }
                        }
                    }
                },
                confirmButton = { TextButton(onClick = { viewModel.aprobar(d.alerta); dialogo = null }) { Text("Marcar atendido") } },
                dismissButton = { TextButton(onClick = { dialogo = Dialogo.Rechazar(d.alerta) }) { Text("Rechazar", color = AdminColor.rojo) } },
            )
        }
        is Dialogo.AprobarTraslado -> AlertDialog(
            onDismissRequest = { dialogo = null },
            title = { Text("¿Autorizar traslado?") },
            text = { AdminTexto("${d.alerta.descripcion}.\nEl proveedor pasará a la nueva zona y quedará registrado en auditoría.", 14) },
            confirmButton = { TextButton(onClick = { viewModel.aprobar(d.alerta); dialogo = null }) { Text("Autorizar") } },
            dismissButton = { TextButton(onClick = { dialogo = null }) { Text("Cancelar") } },
        )
        is Dialogo.Rechazar -> {
            var motivo by remember(d) { mutableStateOf("") }
            val pideMotivo = d.alerta.id.startsWith("traslado:")
            AlertDialog(
                onDismissRequest = { dialogo = null },
                title = { Text(if (d.alerta.tipo == TipoAlertaAdmin.TRASLADO) "Rechazar traslado" else "Rechazar reclamo") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        AdminTexto(d.alerta.descripcion, 13, AdminColor.gris)
                        if (pideMotivo) OutlinedTextField(motivo, { motivo = it }, label = { Text("Motivo") }, modifier = Modifier.fillMaxWidth())
                    }
                },
                confirmButton = {
                    TextButton(onClick = { viewModel.rechazar(d.alerta, motivo); dialogo = null }, enabled = !pideMotivo || motivo.isNotBlank()) {
                        Text("Rechazar", color = AdminColor.rojo)
                    }
                },
                dismissButton = { TextButton(onClick = { dialogo = null }) { Text("Cancelar") } },
            )
        }
        null -> Unit
    }

    return AccionesAlerta(
        principal = { alerta ->
            when (alerta.tipo) {
                TipoAlertaAdmin.CONFLICTO -> alNavegar(Pantalla.AdminConflictos)
                TipoAlertaAdmin.CALIDAD -> alNavegar(Pantalla.AdminCalidad(alerta.proveedorId))
                TipoAlertaAdmin.RECLAMO -> dialogo = Dialogo.Reclamo(alerta)
                TipoAlertaAdmin.TRASLADO -> dialogo = Dialogo.AprobarTraslado(alerta)
            }
        },
        descartar = { alerta ->
            when (alerta.tipo) {
                TipoAlertaAdmin.RECLAMO, TipoAlertaAdmin.TRASLADO -> dialogo = Dialogo.Rechazar(alerta)
                else -> viewModel.ocultar(alerta)
            }
        },
    )
}

@Composable
fun AdminAlertasScreen(
    filtroInicial: TipoAlertaAdmin?,
    alNavegar: (Pantalla) -> Unit,
    alVolver: () -> Unit,
    viewModel: AdminAlertasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    var filtro by rememberSaveable { mutableStateOf(filtroInicial) }
    val acciones = recordarAccionesAlerta(viewModel, estado, alNavegar)
    val visibles = estado.alertas.filter { filtro == null || it.tipo == filtro }
    val ahora = Clock.System.now().toEpochMilliseconds()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Alertas", "${estado.alertas.size} sin resolver", alVolver = alVolver)
        LazyRow(
            contentPadding = PaddingValues(horizontal = 16.dp, vertical = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            item { AdminChip("Todas", filtro == null, cantidad = estado.alertas.size) { filtro = null } }
            items(TipoAlertaAdmin.entries) { tipo ->
                AdminChip(tipo.etiqueta(), filtro == tipo, tipo.color(), estado.cuenta(tipo)) { filtro = tipo }
            }
        }
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            estado.mensaje?.let { item { AdminMensaje(it, false, viewModel::limpiarMensaje) } }
            estado.error?.let { item { AdminMensaje(it, true, viewModel::limpiarMensaje) } }
            when {
                estado.cargando -> item { AdminCargando() }
                visibles.isEmpty() -> item {
                    AdminVacio("Sin alertas pendientes", "Los conflictos, reclamos, traslados y controles de calidad que requieran tu decisión aparecerán aquí.")
                }
                else -> items(visibles, key = { it.id }) { alerta ->
                    TarjetaAlerta(alerta, ahora, alerta.id in estado.procesando, acciones)
                }
            }
        }
    }
}

/** Tarjeta de Alertas del prototipo: borde izquierdo del color del tipo, ícono, acción y Descartar. */
@Composable
fun TarjetaAlerta(alerta: AlertaAdmin, ahora: Long, procesando: Boolean, acciones: AccionesAlerta) {
    val color = alerta.tipo.color()
    AdminCard(radio = 14, acento = color, padding = 14) {
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            AdminTexto(alerta.tipo.icono(), 20)
            Column(Modifier.weight(1f)) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    AdminTexto(alerta.titulo, 14, peso = FontWeight.Bold, modifier = Modifier.weight(1f))
                    AdminTexto(haceTiempo(alerta.ocurridaEn, ahora), 11, AdminColor.gris, modifier = Modifier.padding(start = 8.dp))
                }
                Spacer(Modifier.height(2.dp))
                AdminTexto(alerta.descripcion, 13, AdminColor.gris)
                Spacer(Modifier.height(10.dp))
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    AdminBotonChico(if (procesando) "Procesando…" else alerta.tipo.accion(), AdminColor.blanco, color, { acciones.principal(alerta) }, !procesando)
                    AdminBotonChico(
                        if (alerta.tipo == TipoAlertaAdmin.CONFLICTO || alerta.tipo == TipoAlertaAdmin.CALIDAD) "Ocultar" else "Descartar",
                        AdminColor.gris, AdminColor.crema, { acciones.descartar(alerta) }, !procesando,
                    )
                }
            }
        }
    }
}
