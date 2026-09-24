package pe.ecolecta.presentation.admin.dashboard

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.Refresh
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.TipoAlertaAdmin
import kotlinx.datetime.TimeZone
import kotlinx.datetime.todayIn
import pe.ecolecta.presentation.admin.alertas.AdminAlertasViewModel
import pe.ecolecta.presentation.admin.alertas.recordarAccionesAlerta
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminPildoraSync
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.admin.design.fechaLarga
import pe.ecolecta.presentation.admin.design.haceTiempo
import pe.ecolecta.presentation.admin.alertas.etiqueta
import pe.ecolecta.presentation.navegacion.Pantalla
import kotlin.time.Clock

private data class Mosaico(val etiqueta: String, val valor: Int, val icono: String, val color: Color, val fondo: Color, val destino: Pantalla)

private data class Modulo(val etiqueta: String, val icono: String, val destino: Pantalla)

@Composable
fun AdminDashboardScreen(
    alNavegar: (Pantalla) -> Unit,
    viewModel: AdminDashboardViewModel = koinViewModel(),
    alertasViewModel: AdminAlertasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    val alertas by alertasViewModel.uiState.collectAsState()
    val acciones = recordarAccionesAlerta(alertasViewModel, alertas, alNavegar)
    val ahora = Clock.System.now().toEpochMilliseconds()
    val resumen = estado.resumen

    Column(Modifier.fillMaxSize().background(AdminColor.crema).verticalScroll(rememberScrollState())) {
        // Cabecera verde oscuro con las tres cifras del día.
        Column(Modifier.fillMaxWidth().background(AdminColor.verdeOscuro).padding(start = 20.dp, end = 20.dp, top = 16.dp, bottom = 32.dp)) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
                Column(Modifier.weight(1f)) {
                    AdminTexto("Administrador · ${fechaLarga(Clock.System.todayIn(TimeZone.currentSystemDefault()))}", 12, Color.White.copy(alpha = 0.7f))
                    AdminTexto(estado.nombreAdmin.ifBlank { "Administrador" }, 20, Color.White, FontWeight.Bold, maxLineas = 1)
                }
                Column(horizontalAlignment = Alignment.End) {
                    AdminPildoraSync(resumen?.entregasPendientes ?: 0)
                    IconButton(onClick = viewModel::cargar, modifier = Modifier.size(36.dp)) {
                        Icon(Icons.Outlined.Refresh, contentDescription = "Actualizar", tint = Color.White.copy(alpha = 0.8f), modifier = Modifier.size(20.dp))
                    }
                }
            }
            Spacer(Modifier.height(8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                listOf(
                    "Litros hoy" to (resumen?.let { cifra(it.litrosHoy) } ?: "—"),
                    "Jornadas" to (resumen?.jornadasAbiertas?.toString() ?: "—"),
                    "Proveedores" to (resumen?.proveedoresActivos?.toString() ?: "—"),
                ).forEach { (etiqueta, valor) ->
                    Column(
                        Modifier.weight(1f).clip(RoundedCornerShape(14.dp)).background(Color.White.copy(alpha = 0.1f)).padding(horizontal = 10.dp, vertical = 12.dp),
                        horizontalAlignment = Alignment.CenterHorizontally,
                    ) {
                        AdminTexto(valor, 20, Color.White, FontWeight.ExtraBold, maxLineas = 1)
                        AdminTexto(etiqueta, 10, Color.White.copy(alpha = 0.65f), maxLineas = 1)
                    }
                }
            }
        }

        Column(Modifier.offset(y = (-12).dp).padding(horizontal = 16.dp)) {
            estado.error?.let { AdminMensaje(it, true, viewModel::cargar, Modifier.padding(bottom = 12.dp)) }
            estado.avisoCambios?.let { AdminMensaje(it, estado.avisoCambiosEsError, viewModel::ocultarAvisoCambios, Modifier.padding(bottom = 12.dp)) }
            alertas.mensaje?.let { AdminMensaje(it, false, alertasViewModel::limpiarMensaje, Modifier.padding(bottom = 12.dp)) }
            alertas.error?.let { AdminMensaje(it, true, alertasViewModel::limpiarMensaje, Modifier.padding(bottom = 12.dp)) }

            val mosaicos = listOf(
                Mosaico("Reclamos", alertas.cuenta(TipoAlertaAdmin.RECLAMO), "📝", AdminColor.ambar, AdminColor.ambarSuave, Pantalla.AdminAlertas(TipoAlertaAdmin.RECLAMO)),
                Mosaico("Traslados", alertas.cuenta(TipoAlertaAdmin.TRASLADO), "🚛", AdminColor.azul, AdminColor.azulSuave, Pantalla.AdminAlertas(TipoAlertaAdmin.TRASLADO)),
                Mosaico("Conflictos sync", resumen?.entregasEnConflicto ?: 0, "⚡", AdminColor.rojo, AdminColor.rojoSuave, Pantalla.AdminConflictos),
                Mosaico("Liquidaciones", estado.liquidacionesPorAprobar, "💰", AdminColor.verde, AdminColor.verdeSuave, Pantalla.AdminReportes),
            )
            mosaicos.chunked(2).forEach { fila ->
                Row(Modifier.padding(bottom = 12.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    fila.forEach { m ->
                        Column(
                            Modifier.weight(1f).clip(RoundedCornerShape(14.dp)).background(m.fondo).clickable { alNavegar(m.destino) }.padding(horizontal = 12.dp, vertical = 14.dp),
                            verticalArrangement = Arrangement.spacedBy(4.dp),
                        ) {
                            AdminTexto(m.icono, 20)
                            AdminTexto(m.valor.toString(), 22, m.color, FontWeight.ExtraBold)
                            AdminTexto(m.etiqueta, 12, m.color, FontWeight.SemiBold)
                        }
                    }
                }
            }

            Spacer(Modifier.height(4.dp))
            AdminSeccion("Acciones pendientes")
            val pendientes = alertas.alertas.take(3)
            if (!alertas.cargando && pendientes.isEmpty()) {
                AdminVacio("Todo al día", "No hay conflictos, reclamos, traslados ni análisis por revisar.", Modifier.padding(bottom = 10.dp))
            }
            pendientes.forEach { alerta ->
                val procesando = alerta.id in alertas.procesando
                AdminCard(Modifier.padding(bottom = 10.dp), radio = 14, padding = 14, onClick = { acciones.principal(alerta) }) {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f)) {
                            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                Box(
                                    Modifier.clip(RoundedCornerShape(6.dp)).background(if (alerta.urgente) AdminColor.rojoSuave else AdminColor.crema)
                                        .padding(horizontal = 8.dp, vertical = 2.dp),
                                ) { AdminTexto(alerta.tipo.etiqueta(), 11, if (alerta.urgente) AdminColor.rojo else AdminColor.gris, FontWeight.Bold) }
                                AdminTexto(haceTiempo(alerta.ocurridaEn, ahora), 11, AdminColor.gris)
                            }
                            Spacer(Modifier.height(2.dp))
                            AdminTexto(alerta.descripcion, 14, peso = FontWeight.SemiBold, maxLineas = 2)
                        }
                        Spacer(Modifier.size(8.dp))
                        BotonCuadrado("✓", AdminColor.verde, AdminColor.verdeSuave, !procesando) { acciones.principal(alerta) }
                        Spacer(Modifier.size(6.dp))
                        BotonCuadrado("✕", AdminColor.rojo, AdminColor.rojoSuave, !procesando) { acciones.descartar(alerta) }
                    }
                }
            }
            AdminBoton("Ver todas las alertas", { alNavegar(Pantalla.AdminAlertas()) }, Modifier.padding(top = 4.dp))

            Spacer(Modifier.height(20.dp))
            AdminSeccion("Módulos")
            val modulos = listOf(
                Modulo("Proveedores", "👥", Pantalla.AdminProveedores),
                Modulo("Entregas", "🥛", Pantalla.AdminEntregas),
                Modulo("Calidad", "🔬", Pantalla.AdminCalidad()),
                Modulo("Conflictos", "⚡", Pantalla.AdminConflictos),
                Modulo("Traslados", "🚛", Pantalla.AdminTraslados),
                Modulo("Auditoría", "🗂️", Pantalla.AdminAuditoria),
            )
            modulos.chunked(3).forEach { fila ->
                Row(Modifier.padding(bottom = 10.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    fila.forEach { m ->
                        AdminCard(Modifier.weight(1f), radio = 14, padding = 12, onClick = { alNavegar(m.destino) }) {
                            Column(Modifier.fillMaxWidth(), horizontalAlignment = Alignment.CenterHorizontally) {
                                AdminTexto(m.icono, 22)
                                Spacer(Modifier.height(4.dp))
                                AdminTexto(m.etiqueta, 12, peso = FontWeight.SemiBold, maxLineas = 1)
                            }
                        }
                    }
                }
            }
            AdminTexto(
                "Cifras calculadas con los datos guardados en este dispositivo.",
                11, AdminColor.gris, modifier = Modifier.padding(top = 4.dp, bottom = 16.dp),
            )
        }
    }
}

@Composable
private fun BotonCuadrado(texto: String, color: Color, fondo: Color, habilitado: Boolean, onClick: () -> Unit) {
    Box(
        Modifier.size(34.dp).clip(RoundedCornerShape(8.dp)).background(fondo).clickable(enabled = habilitado, onClick = onClick),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(texto, 13, color, FontWeight.Bold) }
}
