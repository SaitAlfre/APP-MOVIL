package pe.ecolecta.presentation.admin.reportes

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoLiquidacion
import pe.ecolecta.domain.model.LiquidacionSemanal
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminBotonChico
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminCifras
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminDialogo
import pe.ecolecta.presentation.admin.design.AdminCampo
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.admin.design.rangoSemana
import pe.ecolecta.presentation.admin.design.soles
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun AdminReportesScreen(alVolver: () -> Unit, viewModel: AdminReportesViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    var aprobando by remember { mutableStateOf<LiquidacionSemanal?>(null) }
    var pagando by remember { mutableStateOf<LiquidacionSemanal?>(null) }
    var aviso by rememberSaveable { mutableStateOf("") }

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Reportes", "Liquidaciones, producción y avisos", alVolver = alVolver)
        Column(
            Modifier.fillMaxSize().imePadding().verticalScroll(rememberScrollState()).padding(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 24.dp),
        ) {
            estado.mensaje?.let { AdminMensaje(it, false, viewModel::limpiarMensaje, Modifier.padding(bottom = 12.dp)) }
            estado.error?.let { AdminMensaje(it, true, viewModel::limpiarMensaje, Modifier.padding(bottom = 12.dp)) }

            AdminSeccion("Liquidaciones semanales")
            when {
                estado.cargando -> AdminCargando()
                estado.liquidaciones.all { it.entregas == 0 } -> AdminVacio(
                    "Aún no hay entregas", "Las semanas (jueves a miércoles) con entregas registradas aparecerán aquí para liquidarlas.",
                    Modifier.padding(bottom = 12.dp),
                )
                else -> estado.liquidaciones.filter { it.entregas > 0 || it.estado == EstadoLiquidacion.EN_CURSO }.forEach { semana ->
                    TarjetaLiquidacion(semana, estado.procesando, { aprobando = semana }, { pagando = semana })
                }
            }

            Spacer(Modifier.height(8.dp))
            AdminSeccion("Litros por zona · semana en curso")
            val porZona = estado.litrosPorZona
            if (porZona.isEmpty()) {
                AdminVacio("Sin litros esta semana", "Cuando los acopiadores registren entregas verás aquí el aporte de cada zona.", Modifier.padding(bottom = 12.dp))
            } else {
                AdminCard(Modifier.padding(bottom = 12.dp)) {
                    val maximo = porZona.maxOf { it.second }.coerceAtLeast(1.0)
                    porZona.forEachIndexed { i, (zona, litros) ->
                        if (i > 0) Spacer(Modifier.height(10.dp))
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            AdminTexto(zona, 13, peso = FontWeight.SemiBold, modifier = Modifier.weight(1f), maxLineas = 1)
                            AdminTexto("${cifra(litros)} L", 13, AdminColor.gris, FontWeight.Bold)
                        }
                        Spacer(Modifier.height(4.dp))
                        Box(Modifier.fillMaxWidth().height(8.dp).clip(RoundedCornerShape(4.dp)).background(AdminColor.crema)) {
                            Box(Modifier.fillMaxWidth((litros / maximo).toFloat()).fillMaxHeight().clip(RoundedCornerShape(4.dp)).background(AdminColor.verde))
                        }
                    }
                }
            }

            Spacer(Modifier.height(8.dp))
            AdminSeccion("Publicar aviso")
            AdminCard {
                OutlinedTextField(
                    value = aviso,
                    onValueChange = { if (it.length <= 500) aviso = it },
                    modifier = Modifier.fillMaxWidth().height(110.dp),
                    placeholder = { Text("Escribe un comunicado urgente para todos los proveedores...") },
                    shape = RoundedCornerShape(12.dp),
                    colors = OutlinedTextFieldDefaults.colors(
                        unfocusedContainerColor = AdminColor.crema, focusedContainerColor = AdminColor.blanco,
                        unfocusedBorderColor = AdminColor.borde, focusedBorderColor = AdminColor.verde,
                    ),
                )
                AdminTexto(
                    "${aviso.length}/500 · Se muestra en el inicio del portal de cada proveedor. No llega a Alertas ni a los acopiadores.",
                    11, AdminColor.gris, modifier = Modifier.padding(vertical = 6.dp),
                )
                AdminBoton(
                    if (estado.procesando) "Publicando…" else "Publicar comunicado",
                    { viewModel.publicar(aviso) { aviso = "" } },
                    habilitado = aviso.trim().length >= 5 && !estado.procesando,
                    radio = 10,
                )
            }

            Spacer(Modifier.height(16.dp))
            AdminSeccion("Avisos publicados")
            if (estado.comunicados.isEmpty()) {
                AdminVacio("Sin avisos publicados", "Los comunicados que publiques aparecerán aquí; puedes retirarlos cuando ya no apliquen.")
            } else {
                estado.comunicados.take(10).forEach { c ->
                    AdminCard(Modifier.padding(bottom = 10.dp), radio = 14, acento = AdminColor.verde, padding = 14) {
                        AdminTexto(c.mensaje, 14)
                        Spacer(Modifier.height(6.dp))
                        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                            AdminTexto("${c.autorNombre} · ${formatearFechaHora(c.publicadoEn)}", 11, AdminColor.gris, modifier = Modifier.weight(1f))
                            AdminBotonChico("Retirar", AdminColor.rojo, AdminColor.rojoSuave, { viewModel.eliminarComunicado(c.id) }, !estado.procesando)
                        }
                    }
                }
            }
        }
    }

    aprobando?.let { semana ->
        var precio by remember(semana) { mutableStateOf(estado.ultimoPrecio?.let { soles(it).removePrefix("S/ ") } ?: "") }
        val valor = precio.replace(',', '.').toDoubleOrNull()
        AdminDialogo(
            titulo = "Aprobar liquidación",
            textoConfirmar = "Aprobar",
            onConfirmar = { viewModel.aprobar(semana.desde, precio); aprobando = null },
            onCancelar = { aprobando = null },
            habilitado = valor != null && valor > 0,
        ) {
            AdminTexto("${rangoSemana(semana.desde, semana.hasta)} · ${cifra(semana.litros)} L de ${semana.proveedores} proveedores", 14, peso = FontWeight.SemiBold)
            AdminCampo(precio, { precio = it }, "Precio por litro (S/)", teclado = KeyboardType.Decimal)
            valor?.let { AdminTexto("Total a liquidar: ${soles(semana.litros * it)}", 15, AdminColor.verde, FontWeight.Bold) }
            AdminTexto("Se genera una liquidación por proveedor con sus litros de la semana. Cada proveedor la verá en «Mis pagos».", 12, AdminColor.gris)
            AdminTexto(
                "Al aprobarla, las entregas de esta semana quedan bloqueadas: ya no se podrán corregir ni anular, para que el pago no quede desactualizado.",
                12, AdminColor.ambarTexto, FontWeight.Medium,
            )
        }
    }

    pagando?.let { semana ->
        AdminDialogo(
            titulo = "¿Marcar como pagada?",
            textoConfirmar = "Marcar pagada",
            onConfirmar = { viewModel.pagar(semana.desde); pagando = null },
            onCancelar = { pagando = null },
        ) {
            AdminTexto(
                "${rangoSemana(semana.desde, semana.hasta)} · ${semana.total?.let { soles(it) } ?: ""}. Los proveedores verán su pago como «Pagado» y podrán descargar su comprobante.",
                14,
            )
        }
    }
}

@Composable
private fun TarjetaLiquidacion(semana: LiquidacionSemanal, procesando: Boolean, alAprobar: () -> Unit, alPagar: () -> Unit) {
    AdminCard(Modifier.padding(bottom = 12.dp)) {
        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f)) {
                AdminTexto(rangoSemana(semana.desde, semana.hasta), 13, AdminColor.gris, FontWeight.Bold)
                AdminTexto(semana.total?.let { soles(it) } ?: "${cifra(semana.litros)} L", 22, peso = FontWeight.ExtraBold)
            }
            when (semana.estado) {
                EstadoLiquidacion.EN_CURSO -> AdminEtiqueta("En curso", AdminColor.azul, AdminColor.azulSuave)
                EstadoLiquidacion.POR_APROBAR -> AdminEtiqueta("En revisión", AdminColor.ambarTexto, AdminColor.ambarSuave)
                EstadoLiquidacion.APROBADA -> AdminEtiqueta("Aprobada", AdminColor.verde, AdminColor.verdeSuave)
                EstadoLiquidacion.PAGADA -> AdminEtiqueta("Pagada", AdminColor.verde, AdminColor.verdeSuave)
            }
        }
        Spacer(Modifier.height(12.dp))
        AdminCifras(
            listOf(
                "Litros" to "${cifra(semana.litros)} L",
                "Proveedores" to semana.proveedores.toString(),
                "Precio/L" to (semana.precio?.let { soles(it) } ?: "—"),
            ),
            tamanoValor = 13,
        )
        when (semana.estado) {
            EstadoLiquidacion.POR_APROBAR -> AdminBoton(
                "Aprobar liquidación", alAprobar, Modifier.padding(top = 10.dp), habilitado = !procesando && semana.entregas > 0, radio = 10, alto = 42,
            )
            EstadoLiquidacion.APROBADA -> AdminBoton(
                "Marcar como pagada", alPagar, Modifier.padding(top = 10.dp), habilitado = !procesando, color = AdminColor.verde, radio = 10, alto = 42,
            )
            EstadoLiquidacion.EN_CURSO -> AdminTexto("La semana cierra el miércoles; podrás liquidarla desde el jueves.", 12, AdminColor.gris, modifier = Modifier.padding(top = 8.dp))
            EstadoLiquidacion.PAGADA -> Unit
        }
    }
}
