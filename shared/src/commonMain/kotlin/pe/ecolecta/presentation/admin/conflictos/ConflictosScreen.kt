package pe.ecolecta.presentation.admin.conflictos

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.OrigenValorConflicto
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminBotonBorde
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminCifras
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminDialogoMotivo
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun ConflictosScreen(alVolver: () -> Unit = {}, viewModel: ConflictosViewModel = koinViewModel()) {
    val s by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Conflictos de sincronización", subtitulo = "${s.conflictos.size} por resolver", alVolver = alVolver)
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                AdminCard(color = AdminColor.grisSuave, radio = 14, padding = 12) {
                    AdminTexto(
                        "Pendiente: aún no se envía. Error: se intentó enviar y falló. Conflicto: el teléfono y el servidor tienen " +
                            "valores distintos para la misma entrega y alguien debe decidir cuál vale. Solo los conflictos se resuelven aquí.",
                        12, AdminColor.gris,
                    )
                }
            }
            s.mensaje?.let { item { AdminMensaje(it, false, viewModel::limpiarMensaje) } }
            s.error?.let { item { AdminMensaje(it, true, {}) } }
            when {
                s.cargando -> item { AdminCargando() }
                s.conflictos.isEmpty() -> item {
                    AdminVacio("No hay conflictos pendientes", "Es lo normal: los conflictos solo aparecen cuando el servidor devuelve un valor distinto al del teléfono.")
                }
                else -> items(s.conflictos, key = { it.id }) { entrega ->
                    AdminCard(acento = AdminColor.morado) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminTexto(s.nombreProveedor(entrega.proveedorId), 15, peso = FontWeight.Bold, modifier = Modifier.weight(1f), maxLineas = 1)
                            AdminEtiqueta(TextosEstado.larga(entrega.syncState), AdminColor.morado, AdminColor.moradoSuave)
                        }
                        AdminTexto("Entrega del ${formatearFechaHora(entrega.registradoEn)}", 12, AdminColor.gris, modifier = Modifier.padding(bottom = 8.dp))
                        AdminCifras(
                            listOf(
                                "En el teléfono" to "${cifra(entrega.litros)} L · ${entrega.tachos} t",
                                "En el servidor" to (entrega.litrosServidor?.let { l -> "${cifra(l)} L" + (entrega.tachosServidor?.let { t -> " · ${t.toInt()} t" } ?: "") } ?: "—"),
                            ),
                        )
                        AdminTexto("Motivo: ${entrega.motivoConflicto ?: "no informado"}", 12, AdminColor.texto, modifier = Modifier.padding(top = 8.dp))
                        AdminTexto(
                            "«Usar teléfono» conserva el valor local y la entrega queda pendiente de reenvío. " +
                                "«Usar servidor» reemplaza el valor local y la entrega queda sincronizada. Ambas piden motivo y quedan en Auditoría.",
                            11, AdminColor.gris, modifier = Modifier.padding(top = 6.dp, bottom = 10.dp),
                        )
                        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            AdminBoton("Usar teléfono", { viewModel.pedirMotivo(entrega.id, OrigenValorConflicto.LOCAL) }, Modifier.weight(1f))
                            AdminBotonBorde("Usar servidor", AdminColor.verdeOscuro, { viewModel.pedirMotivo(entrega.id, OrigenValorConflicto.SERVIDOR) }, Modifier.weight(1f))
                        }
                    }
                }
            }
        }
    }

    s.resolucion?.let { (entregaId, origen) ->
        val entrega = s.conflictos.firstOrNull { it.id == entregaId }
        val valor = when (origen) {
            OrigenValorConflicto.LOCAL -> entrega?.let { "${cifra(it.litros)} L del teléfono" }
            OrigenValorConflicto.SERVIDOR -> entrega?.litrosServidor?.let { "${cifra(it)} L del servidor" }
        } ?: "el valor elegido"
        AdminDialogoMotivo(
            titulo = "Resolver conflicto",
            explicacion = "Se conservará $valor. " + when (origen) {
                OrigenValorConflicto.LOCAL -> "La entrega quedará pendiente de envío."
                OrigenValorConflicto.SERVIDOR -> "La entrega quedará sincronizada."
            },
            textoConfirmar = "Resolver",
            onConfirmar = viewModel::resolver,
            onCancelar = viewModel::cancelar,
            procesando = s.procesando,
            error = s.errorDialogo,
        )
    }
}
