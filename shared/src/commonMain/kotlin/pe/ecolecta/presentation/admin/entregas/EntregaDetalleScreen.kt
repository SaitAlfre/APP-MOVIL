package pe.ecolecta.presentation.admin.entregas

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.admin.design.AdminBoton
import pe.ecolecta.presentation.admin.design.AdminBotonBorde
import pe.ecolecta.presentation.admin.design.AdminCampo
import pe.ecolecta.presentation.admin.design.AdminCard
import pe.ecolecta.presentation.admin.design.AdminCargando
import pe.ecolecta.presentation.admin.design.AdminCifras
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminDialogo
import pe.ecolecta.presentation.admin.design.AdminDialogoMotivo
import pe.ecolecta.presentation.admin.design.AdminMensaje
import pe.ecolecta.presentation.admin.design.AdminSeccion
import pe.ecolecta.presentation.admin.design.AdminTexto
import pe.ecolecta.presentation.admin.design.AdminTopBar
import pe.ecolecta.presentation.admin.design.AdminVacio
import pe.ecolecta.presentation.admin.design.EtiquetaEntrega
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.admin.design.cifra
import pe.ecolecta.presentation.design.formatearFechaHora

@Composable
fun EntregaDetalleScreen(
    id: String,
    alVolver: () -> Unit = {},
    viewModel: EntregaDetalleViewModel = koinViewModel(key = id, parameters = { parametersOf(id) }),
) {
    val s by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
        AdminTopBar("Detalle de entrega", alVolver = alVolver)
        Column(
            Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            s.mensaje?.let { AdminMensaje(it, false, viewModel::limpiarMensaje) }
            if (s.cargando) {
                AdminCargando()
                return@Column
            }
            val entrega = s.entrega
            if (entrega == null) {
                AdminVacio("Entrega no disponible", s.error ?: "No se pudo cargar la entrega.")
                return@Column
            }
            s.error?.let { AdminMensaje(it, true, {}) }

            AdminCard {
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    AdminTexto(s.proveedorNombre, 18, peso = FontWeight.SemiBold, modifier = Modifier.weight(1f))
                    EtiquetaEntrega(entrega, larga = true)
                }
                AdminTexto("Registrada: ${formatearFechaHora(entrega.registradoEn)}", 12, AdminColor.gris, modifier = Modifier.padding(top = 4.dp, bottom = 10.dp))
                AdminCifras(listOf("Litros" to "${cifra(entrega.litros)} L", "Tachos" to entrega.tachos.toString()))
                entrega.observaciones?.let { AdminTexto("Observaciones: $it", 12, AdminColor.gris, modifier = Modifier.padding(top = 8.dp)) }
            }

            when {
                entrega.anulada -> AdminCard(color = AdminColor.rojoSuave) {
                    AdminTexto("Entrega anulada", 14, AdminColor.rojo, FontWeight.Bold)
                    val anulacion = s.anulacion
                    if (anulacion != null) {
                        AdminTexto(
                            "El ${formatearFechaHora(anulacion.ocurridoEn)} por ${s.nombreUsuario(anulacion.usuarioId)}. Motivo: ${anulacion.motivo ?: "no registrado"}.",
                            13, AdminColor.texto, modifier = Modifier.padding(top = 4.dp),
                        )
                    }
                    AdminTexto(
                        "La anulación es definitiva: ya no suma litros ni entra en liquidaciones, y por eso no se puede corregir ni volver a anular. " +
                            "Si fue un error, el acopiador puede registrar una entrega nueva con los datos correctos (quedará con la fecha en que se registre).",
                        12, AdminColor.gris, modifier = Modifier.padding(top = 6.dp),
                    )
                }
                else -> {
                    AdminCard(color = AdminColor.grisSuave) {
                        AdminTexto(TextosEstado.larga(entrega.syncState), 13, peso = FontWeight.SemiBold)
                        AdminTexto(TextosEstado.ayuda(entrega.syncState), 12, AdminColor.gris)
                        if (entrega.syncState == SyncState.ERROR) {
                            AdminTexto(
                                "Motivo del fallo: ${entrega.syncError ?: "no quedó registrado en el teléfono"}.",
                                12, AdminColor.rojo, FontWeight.Medium, Modifier.padding(top = 6.dp),
                            )
                            AdminTexto(
                                "No hay reintento manual: esta versión todavía no envía datos al servidor, así que reintentar no tendría efecto.",
                                11, AdminColor.gris,
                            )
                        }
                    }
                    val bloqueo = s.bloqueo
                    if (bloqueo != null) {
                        AdminCard(color = AdminColor.ambarSuave) {
                            AdminTexto("No se puede corregir ni anular", 13, AdminColor.ambarTexto, FontWeight.Bold)
                            AdminTexto(bloqueo, 12, AdminColor.texto, modifier = Modifier.padding(top = 4.dp))
                        }
                    } else {
                        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            AdminBoton("Corregir", { viewModel.abrirDialogo(DialogoEntrega.CORREGIR) }, Modifier.weight(1f))
                            AdminBotonBorde("Anular", AdminColor.rojo, { viewModel.abrirDialogo(DialogoEntrega.ANULAR) }, Modifier.weight(1f))
                        }
                        AdminTexto(
                            "Corregir cambia litros o tachos; anular la deja sin efecto para siempre. Ambas piden motivo y quedan en Auditoría.",
                            11, AdminColor.gris,
                        )
                    }
                }
            }

            if (s.historial.isNotEmpty()) {
                AdminSeccion("Historial de cambios")
                s.historial.forEach { h ->
                    AdminCard(radio = 14, padding = 12) {
                        AdminTexto("${TextosEstado.accion(h.accion)} · ${formatearFechaHora(h.ocurridoEn)}", 13, peso = FontWeight.SemiBold)
                        AdminTexto("Por ${s.nombreUsuario(h.usuarioId)}", 12, AdminColor.gris)
                        h.motivo?.let { AdminTexto("Motivo: $it", 12) }
                        if (h.accion == AccionAuditoria.CORREGIR || h.accion == AccionAuditoria.RESOLVER_CONFLICTO) {
                            AdminTexto(
                                "${h.valorAntes?.let(TextosEstado::valores) ?: "—"}  →  ${h.valorDespues?.let(TextosEstado::valores) ?: "—"}",
                                12, AdminColor.gris,
                            )
                        }
                    }
                }
            }
        }
    }

    val entrega = s.entrega
    when {
        entrega == null -> Unit
        s.dialogo == DialogoEntrega.CORREGIR -> DialogoCorregir(
            litrosIniciales = cifra(entrega.litros).replace(",", ""),
            tachosIniciales = entrega.tachos.toString(),
            procesando = s.procesando,
            error = s.errorDialogo,
            onConfirmar = viewModel::corregir,
            onCancelar = { viewModel.abrirDialogo(null) },
        )
        s.dialogo == DialogoEntrega.ANULAR -> AdminDialogoMotivo(
            titulo = "Anular entrega",
            explicacion = "La entrega de ${s.proveedorNombre} (${cifra(entrega.litros)} L) dejará de contar en litros y liquidaciones. " +
                "No se puede deshacer ni corregir después.",
            textoConfirmar = "Anular definitivamente",
            onConfirmar = viewModel::anular,
            onCancelar = { viewModel.abrirDialogo(null) },
            procesando = s.procesando,
            error = s.errorDialogo,
            colorConfirmar = AdminColor.rojo,
        )
    }
}

@Composable
private fun DialogoCorregir(
    litrosIniciales: String,
    tachosIniciales: String,
    procesando: Boolean,
    error: String?,
    onConfirmar: (Double, Int, String) -> Unit,
    onCancelar: () -> Unit,
) {
    var litros by rememberSaveable { mutableStateOf(litrosIniciales) }
    var tachos by rememberSaveable { mutableStateOf(tachosIniciales) }
    var motivo by rememberSaveable { mutableStateOf("") }
    val litrosValor = litros.replace(',', '.').toDoubleOrNull()?.takeIf { it.isFinite() && it > 0 }
    val tachosValor = tachos.toIntOrNull()?.takeIf { it > 0 }
    val sinCambios = litrosValor == litrosIniciales.toDoubleOrNull() && tachosValor == tachosIniciales.toIntOrNull()
    val aviso = when {
        litrosValor == null -> "Ingresa litros mayores a 0."
        tachosValor == null -> "Ingresa al menos 1 tacho."
        sinCambios -> "Cambia los litros o los tachos para poder corregir."
        motivo.isBlank() -> "El motivo es obligatorio."
        else -> null
    }

    AdminDialogo(
        titulo = "Corregir entrega",
        textoConfirmar = "Guardar corrección",
        onConfirmar = { if (litrosValor != null && tachosValor != null) onConfirmar(litrosValor, tachosValor, motivo.trim()) },
        onCancelar = onCancelar,
        habilitado = aviso == null,
        procesando = procesando,
    ) {
        AdminTexto("Los valores anteriores y el motivo quedarán en Auditoría.", 13, AdminColor.gris)
        AdminCampo(litros, { litros = it.filter { c -> c.isDigit() || c == '.' || c == ',' }.take(7) }, "Litros", teclado = KeyboardType.Decimal)
        AdminCampo(tachos, { tachos = it.filter(Char::isDigit).take(3) }, "Tachos", teclado = KeyboardType.Number)
        AdminCampo(motivo, { motivo = it.take(300) }, "Motivo (obligatorio)", marcador = "Ej: error de digitación")
        (error ?: aviso)?.let { AdminTexto(it, 12, if (error != null) AdminColor.rojo else AdminColor.gris, FontWeight.Medium) }
    }
}
