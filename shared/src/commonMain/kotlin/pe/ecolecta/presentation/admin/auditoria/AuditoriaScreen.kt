package pe.ecolecta.presentation.admin.auditoria

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
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun AuditoriaScreen(alVolver: () -> Unit = {}, viewModel: AuditoriaViewModel = koinViewModel()) {
    val s by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Auditoría", subtitulo = "Historial de acciones · solo lectura", alVolver = alVolver)
        LazyColumn(
            Modifier.fillMaxSize(),
            contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            item {
                AdminTexto(
                    "Cada corrección, anulación, autorización y cambio de cuenta queda aquí con quién lo hizo y cuándo. " +
                        "Estos registros no se pueden editar ni borrar.",
                    12, AdminColor.gris,
                )
            }
            when {
                s.cargando -> item { AdminCargando() }
                s.error != null -> item { AdminVacio("No se pudo cargar la auditoría", s.error.orEmpty()) }
                s.registros.isEmpty() -> item {
                    AdminVacio("Sin registros todavía", "Las correcciones, anulaciones y resoluciones quedarán registradas aquí.")
                }
                else -> items(s.registros, key = { it.auditoria.id }) { r ->
                    val a = r.auditoria
                    val (color, fondo) = when (a.accion) {
                        AccionAuditoria.ANULAR, AccionAuditoria.RECHAZAR, AccionAuditoria.DESACTIVAR -> AdminColor.rojo to AdminColor.rojoSuave
                        AccionAuditoria.CORREGIR, AccionAuditoria.RESOLVER_CONFLICTO, AccionAuditoria.REABRIR_JORNADA -> AdminColor.ambarTexto to AdminColor.ambarSuave
                        AccionAuditoria.LOGIN, AccionAuditoria.SYNC -> AdminColor.gris to AdminColor.grisSuave
                        else -> AdminColor.verde to AdminColor.verdeSuave
                    }
                    AdminCard(radio = 14, padding = 14) {
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminEtiqueta(TextosEstado.accion(a.accion), color, fondo)
                            AdminTexto(formatearFechaHora(a.ocurridoEn), 11, AdminColor.gris, modifier = Modifier.weight(1f).padding(start = 8.dp))
                        }
                        Column(Modifier.padding(top = 6.dp), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                            AdminTexto(r.sobre, 14, peso = FontWeight.SemiBold)
                            AdminTexto("Por: ${r.autor}", 12, AdminColor.gris)
                            a.motivo?.let { AdminTexto("Motivo: $it", 12) }
                            if (a.valorAntes != null || a.valorDespues != null) {
                                AdminTexto("Antes: ${a.valorAntes?.let(TextosEstado::valores) ?: "—"}", 12, AdminColor.gris)
                                AdminTexto("Después: ${a.valorDespues?.let(TextosEstado::valores) ?: "—"}", 12, AdminColor.gris)
                            }
                        }
                    }
                }
            }
        }
    }
}
