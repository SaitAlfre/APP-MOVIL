package pe.ecolecta.presentation.acopiador.home

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.Assignment
import androidx.compose.material.icons.filled.EventAvailable
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.EventRepeat
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.LocalDrink
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Clock
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonBorde
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSync
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun AcopiadorHomeScreen(
    alRegistrarEntrega: () -> Unit,
    alEscanearQr: () -> Unit,
    alVerResumen: () -> Unit,
    alSincronizar: () -> Unit,
    alAbrirJornada: () -> Unit,
    viewModel: AcopiadorHomeViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()
    var entregaParaCorregir by remember { mutableStateOf<Entrega?>(null) }
    var entregaParaAnular by remember { mutableStateOf<Entrega?>(null) }

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu jornada…")
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoJornada(nombre = estado.nombreUsuario, pendientesSync = estado.pendientesSync)

        Column(
            Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.m),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            if (estado.jornadaTerminada) {
                TarjetaJornadaTerminada(
                    estado = estado,
                    alSincronizar = alSincronizar,
                    alReabrir = viewModel::solicitarReapertura,
                    alCerrarSesion = viewModel::solicitarCierreSesion,
                )
            } else if (!estado.jornadaAbierta) {
                TarjetaSinJornada(alAbrirJornada)
            } else {
                TarjetaJornadaActiva(estado)

                Text(
                    "ACCIONES RÁPIDAS",
                    style = MaterialTheme.typography.labelLarge,
                    color = Colores.textSecundario,
                    modifier = Modifier.padding(top = Espaciado.xs),
                )
                GridAccionesRapidas(
                    alRegistrarEntrega = alRegistrarEntrega,
                    alEscanearQr = alEscanearQr,
                    alVerResumen = alVerResumen,
                    alSincronizar = alSincronizar,
                )

                BotonBorde(
                    texto = if (estado.cerrandoJornada) "Cerrando jornada…" else "Cerrar jornada",
                    color = Colores.peligro,
                    icono = Icons.Filled.EventBusy,
                    habilitado = !estado.cerrandoJornada,
                    cargando = estado.cerrandoJornada,
                    onClick = viewModel::solicitarCierreJornada,
                )
                estado.errorCierreJornada?.let { Banner(mensaje = it, tipo = TipoBanner.ERROR) }
            }

            Text(
                "Últimas entregas",
                style = MaterialTheme.typography.titleLarge,
                color = Colores.textPrimary,
                modifier = Modifier.padding(top = Espaciado.xs),
            )

            if (estado.ultimasEntregas.isEmpty()) {
                EstadoVacio(
                    titulo = "Todavía no registraste entregas hoy",
                    descripcion = "Tus entregas del día aparecerán aquí apenas las registres.",
                    icono = Icons.Filled.Inventory2,
                    textoAccion = if (estado.jornadaAbierta) "Registrar primera entrega" else null,
                    alPresionarAccion = if (estado.jornadaAbierta) alRegistrarEntrega else null,
                )
            } else {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    estado.ultimasEntregas.forEach { entrega ->
                        FilaEntregaDelDia(
                            nombre = estado.nombreProveedor(entrega.proveedorId),
                            entrega = entrega,
                            // Una jornada terminada se consulta, no se edita.
                            onClick = if (estado.jornadaAbierta && puedeEditar(entrega)) ({ entregaParaCorregir = entrega }) else null,
                        )
                    }
                }
            }

            Spacer(Modifier.height(Espaciado.l))
        }
    }

    entregaParaCorregir?.let { entrega ->
        DialogoCorreccionRapida(
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
                    "Se finalizará tu jornada de hoy. Tus entregas, los \"sin recojo\" y los pendientes por " +
                        "sincronizar no se pierden; después del cierre, cualquier corrección queda auditada.",
                )
            },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreJornada) { Text("Cerrar jornada", color = Colores.peligro) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreJornada) { Text("Cancelar") } },
        )
    }

    if (estado.mostrarDialogoReapertura) {
        DialogoReapertura(
            requiereAdmin = estado.reaperturaRequiereAdmin,
            plazoMinutos = estado.plazoReaperturaMinutos,
            reabriendo = estado.reabriendo,
            error = estado.errorReapertura,
            onConfirmar = viewModel::confirmarReapertura,
            onCancelar = viewModel::cancelarReapertura,
        )
    }

    if (estado.mostrarConfirmacionCierreSesion) {
        AlertDialog(
            onDismissRequest = viewModel::cancelarCierreSesion,
            shape = MaterialTheme.shapes.large,
            title = { Text("¿Cerrar sesión?") },
            text = {
                Text(
                    if (estado.pendientesSync > 0) {
                        "Tienes ${estado.pendientesSync} registros sin sincronizar. No se borran: quedan guardados en " +
                            "este teléfono y se enviarán cuando vuelvas a entrar con tu cuenta."
                    } else {
                        "Tus entregas quedan guardadas en este teléfono."
                    },
                )
            },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreSesion) { Text("Cerrar sesión", color = Colores.peligro) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreSesion) { Text("Cancelar") } },
        )
    }
}

/** Resumen de la jornada de hoy ya cerrada: una por día, así que aquí no se ofrece abrir otra. */
@Composable
private fun TarjetaJornadaTerminada(
    estado: AcopiadorHomeUiState,
    alSincronizar: () -> Unit,
    alReabrir: () -> Unit,
    alCerrarSesion: () -> Unit,
) {
    Tarjeta {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f, fill = false)) {
                Text("JORNADA DE HOY", style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                Text(
                    "Ruta ${estado.zonaNombre}",
                    style = MaterialTheme.typography.titleLarge,
                    color = Colores.textPrimary,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            Spacer(Modifier.width(Espaciado.s))
            ChipEstado("Terminada", Colores.textSecundario, mostrarPunto = false)
        }
        Spacer(Modifier.height(Espaciado.xxs))
        val horario = listOfNotNull(
            estado.horaInicio.takeIf { it.isNotBlank() }?.let { "Abierta $it" },
            estado.horaCierre?.let { "cerrada $it" },
        ).joinToString(" · ")
        val detalle = listOfNotNull(horario.takeIf { it.isNotBlank() }, estado.vehiculoInfo.takeIf { it.isNotBlank() })
            .joinToString(" • ")
        if (detalle.isNotBlank()) Text(detalle, style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
        Spacer(Modifier.height(Espaciado.m))
        Row(Modifier.fillMaxWidth()) {
            EstadisticaJornada(formatearLitros(estado.litrosHoy).removeSuffix(" L"), "L", "Litros", Modifier.weight(1f))
            EstadisticaJornada(estado.entregasHoy.toString(), "", "Entregas", Modifier.weight(1f))
            EstadisticaJornada(estado.proveedoresAtendidosHoy.toString(), "prov.", "Atendidos", Modifier.weight(1f))
        }
        Spacer(Modifier.height(Espaciado.m))
        Banner(
            "Ya terminaste tu jornada de hoy. Solo se permite una por día: mañana podrás abrir una nueva.",
            TipoBanner.INFO,
        )
        if (estado.pendientesSync > 0) {
            Spacer(Modifier.height(Espaciado.s))
            Banner(
                "${estado.pendientesSync} registros pendientes de sincronización. Se conservan en este teléfono.",
                TipoBanner.ADVERTENCIA,
            )
            Spacer(Modifier.height(Espaciado.s))
            BotonPrimario("Sincronizar ahora", alSincronizar, icono = Icons.Filled.Sync)
        }
        Spacer(Modifier.height(Espaciado.s))
        BotonBorde(
            texto = "¿Cerraste por error? Reabrir jornada",
            color = Colores.brand,
            icono = Icons.Filled.EventRepeat,
            onClick = alReabrir,
            habilitado = !estado.reabriendo,
        )
        Spacer(Modifier.height(Espaciado.s))
        BotonBorde(
            texto = if (estado.cerrandoSesion) "Cerrando sesión…" else "Cerrar sesión",
            color = Colores.peligro,
            icono = Icons.AutoMirrored.Filled.Logout,
            habilitado = !estado.cerrandoSesion,
            cargando = estado.cerrandoSesion,
            onClick = alCerrarSesion,
        )
    }
}

/**
 * Reapertura controlada: siempre con motivo; pasado el plazo, además, usuario y PIN de un
 * administrador introducidos aquí mismo (no hay aprobación remota desde otro dispositivo).
 */
@Composable
private fun DialogoReapertura(
    requiereAdmin: Boolean,
    plazoMinutos: Int,
    reabriendo: Boolean,
    error: String?,
    onConfirmar: (motivo: String, usuarioAdmin: String, pinAdmin: String) -> Unit,
    onCancelar: () -> Unit,
) {
    var motivo by remember { mutableStateOf("") }
    var usuarioAdmin by remember { mutableStateOf("") }
    var pinAdmin by remember { mutableStateOf("") }
    val completo = motivo.trim().length >= 10 && (!requiereAdmin || (usuarioAdmin.isNotBlank() && pinAdmin.length == 4))

    AlertDialog(
        onDismissRequest = { if (!reabriendo) onCancelar() },
        shape = MaterialTheme.shapes.large,
        title = { Text("Reabrir la jornada de hoy") },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                Text(
                    "Se reabre la MISMA jornada: tus entregas se conservan y no se crea otra. La reapertura y su " +
                        "motivo quedan registrados en la auditoría.",
                    style = MaterialTheme.typography.bodyMedium,
                )
                if (requiereAdmin) {
                    Banner(
                        "Pasaron más de $plazoMinutos minutos desde el cierre. Un administrador debe autorizar aquí, " +
                            "en este teléfono, con su usuario y PIN.",
                        TipoBanner.ADVERTENCIA,
                    )
                }
                CampoTexto(valor = motivo, onValorCambia = { motivo = it }, etiqueta = "Motivo (mín. 10 caracteres)")
                if (requiereAdmin) {
                    CampoTexto(valor = usuarioAdmin, onValorCambia = { usuarioAdmin = it }, etiqueta = "Usuario del administrador")
                    CampoTexto(
                        valor = pinAdmin,
                        onValorCambia = { pinAdmin = it.filter(Char::isDigit).take(4) },
                        etiqueta = "PIN del administrador",
                        esPin = true,
                    )
                }
                error?.let { Banner(it, TipoBanner.ERROR) }
            }
        },
        confirmButton = {
            TextButton(onClick = { onConfirmar(motivo, usuarioAdmin, pinAdmin) }, enabled = completo && !reabriendo) {
                Text(if (reabriendo) "Reabriendo…" else "Reabrir")
            }
        },
        dismissButton = { TextButton(onClick = onCancelar, enabled = !reabriendo) { Text("Cancelar") } },
    )
}

private fun puedeEditar(entrega: Entrega): Boolean = !entrega.anulada && entrega.syncState != SyncState.CONFLICT

/** Encabezado verde con el saludo del día, la fecha y el contador de pendientes por sincronizar. */
@Composable
private fun EncabezadoJornada(nombre: String, pendientesSync: Int) {
    val ahora = remember { Clock.System.now().toLocalDateTime(TimeZone.currentSystemDefault()) }
    val saludo = when (ahora.hour) {
        in 0..11 -> "Buenos días,"
        in 12..18 -> "Buenas tardes,"
        else -> "Buenas noches,"
    }
    val fecha = "${diaSemana(ahora.dayOfWeek.ordinal)}, ${ahora.day} de ${mesLargo(ahora.monthNumber)}"

    Surface(color = Colores.brand, modifier = Modifier.fillMaxWidth()) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = Espaciado.l, vertical = Espaciado.l),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.Top,
        ) {
            Column(Modifier.weight(1f, fill = false)) {
                Text(saludo, style = MaterialTheme.typography.bodyLarge, color = Colores.onBrand.copy(alpha = 0.85f))
                Text(
                    nombre.ifBlank { "Acopiador" },
                    style = MaterialTheme.typography.headlineSmall,
                    color = Colores.onBrand,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                )
                Spacer(Modifier.height(Espaciado.xxs))
                Text(fecha, style = MaterialTheme.typography.bodyMedium, color = Colores.onBrand.copy(alpha = 0.85f))
            }
            if (pendientesSync > 0) {
                Spacer(Modifier.width(Espaciado.s))
                Surface(shape = MaterialTheme.shapes.extraLarge, color = Colores.onBrand) {
                    Text(
                        "$pendientesSync pendientes",
                        style = MaterialTheme.typography.labelMedium,
                        color = Colores.brandText,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = Espaciado.s, vertical = Espaciado.xxs),
                    )
                }
            }
        }
    }
}

@Composable
private fun TarjetaSinJornada(alAbrirJornada: () -> Unit) {
    Tarjeta {
        Text("No tienes una jornada abierta", style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
        Spacer(Modifier.height(Espaciado.xxs))
        Text(
            "Abre tu jornada para registrar entregas y ver a los proveedores de tu ruta de hoy.",
            style = MaterialTheme.typography.bodyMedium,
            color = Colores.textSecundario,
        )
        Spacer(Modifier.height(Espaciado.s))
        BotonPrimario("Abrir jornada", alAbrirJornada, icono = Icons.Filled.EventAvailable)
    }
}

/** Tarjeta principal de la jornada: ruta/zona/vehículo, estado, y los tres números que importan hoy. */
@Composable
private fun TarjetaJornadaActiva(estado: AcopiadorHomeUiState) {
    Tarjeta {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f, fill = false)) {
                Text("JORNADA ACTIVA", style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                Text(
                    "Ruta ${estado.zonaNombre}",
                    style = MaterialTheme.typography.titleLarge,
                    color = Colores.textPrimary,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            Spacer(Modifier.width(Espaciado.s))
            ChipEstado("Abierta", Colores.exito, mostrarPunto = false)
        }
        Spacer(Modifier.height(Espaciado.xxs))
        val detalleRuta = listOfNotNull(
            "Zona ${estado.zonaNombre}".takeIf { estado.zonaNombre.isNotBlank() },
            estado.vehiculoInfo.takeIf { it.isNotBlank() },
        ).joinToString(" • ")
        if (detalleRuta.isNotBlank()) {
            Text(detalleRuta, style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
        }
        Spacer(Modifier.height(Espaciado.m))
        Row(Modifier.fillMaxWidth()) {
            EstadisticaJornada(formatearLitros(estado.litrosHoy).removeSuffix(" L"), "L", "Litros", Modifier.weight(1f))
            EstadisticaJornada(estado.proveedoresAtendidosHoy.toString(), "prov.", "Atendidos", Modifier.weight(1f))
            EstadisticaJornada(estado.proveedoresPendientesHoy.toString(), "prov.", "Pendientes", Modifier.weight(1f))
        }
        if (estado.pendientesSync > 0) {
            Spacer(Modifier.height(Espaciado.m))
            Banner("${estado.pendientesSync} entregas pendientes de sincronización", TipoBanner.ADVERTENCIA)
        }
    }
}

@Composable
private fun EstadisticaJornada(valor: String, unidad: String, etiqueta: String, modifier: Modifier = Modifier) {
    Column(modifier, horizontalAlignment = Alignment.CenterHorizontally) {
        Text(valor, style = MaterialTheme.typography.headlineMedium, color = Colores.textPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis)
        Text(unidad, style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
        Spacer(Modifier.height(2.dp))
        Text(etiqueta, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}

/** Grilla 2×2 de las acciones que el acopiador usa a cada rato: registrar, escanear, resumir, sincronizar. */
@Composable
private fun GridAccionesRapidas(
    alRegistrarEntrega: () -> Unit,
    alEscanearQr: () -> Unit,
    alVerResumen: () -> Unit,
    alSincronizar: () -> Unit,
) {
    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
        Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            TarjetaAccion("Registrar entrega", Icons.Filled.LocalDrink, Colores.brand, Colores.onBrand, Modifier.weight(1f), alRegistrarEntrega)
            TarjetaAccion("Escanear QR", Icons.Filled.QrCodeScanner, Colores.info, Color.White, Modifier.weight(1f), alEscanearQr)
        }
        Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            TarjetaAccion("Ver resumen", Icons.Filled.Assignment, Colores.surface, Colores.textPrimary, Modifier.weight(1f), alVerResumen, conBorde = true)
            TarjetaAccion("Sincronizar", Icons.Filled.Sync, Colores.surface, Colores.textPrimary, Modifier.weight(1f), alSincronizar, conBorde = true)
        }
    }
}

@Composable
private fun TarjetaAccion(
    texto: String,
    icono: ImageVector,
    contenedor: Color,
    contenido: Color,
    modifier: Modifier = Modifier,
    onClick: () -> Unit,
    conBorde: Boolean = false,
) {
    Surface(
        modifier = modifier.heightIn(min = 88.dp).clickable(onClick = onClick),
        shape = MaterialTheme.shapes.medium,
        color = contenedor,
        border = if (conBorde) BorderStroke(1.dp, Colores.borde) else null,
        tonalElevation = if (conBorde) 1.dp else 0.dp,
        shadowElevation = if (conBorde) 1.dp else 0.dp,
    ) {
        Column(
            Modifier.fillMaxSize().padding(Espaciado.m),
            verticalArrangement = Arrangement.SpaceBetween,
        ) {
            Icon(icono, contentDescription = null, tint = contenido, modifier = Modifier.size(24.dp))
            Spacer(Modifier.height(Espaciado.xs))
            Text(
                texto,
                style = MaterialTheme.typography.titleSmall,
                color = contenido,
                fontWeight = FontWeight.SemiBold,
                maxLines = 2,
                overflow = TextOverflow.Ellipsis,
            )
        }
    }
}

/** Fila de entrega vista por el acopiador: manda de quién es, no cuántos litros. */
@Composable
private fun FilaEntregaDelDia(nombre: String, entrega: Entrega, onClick: (() -> Unit)?) {
    Tarjeta(onClick = onClick) {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Row(
                Modifier.weight(1f, fill = false),
                horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(Icons.Filled.Person, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(20.dp))
                Column {
                    Text(nombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis)
                    Text(
                        "${formatearLitros(entrega.litros)} · ${formatearHora(entrega.registradoEn)}",
                        style = MaterialTheme.typography.bodyMedium,
                        color = Colores.textSecundario,
                    )
                }
            }
            Spacer(Modifier.width(Espaciado.s))
            ChipSync(entrega)
        }
    }
}

@Composable
private fun DialogoCorreccionRapida(
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
                Text(
                    "¿Te equivocaste de proveedor? Puedes anular esta entrega en su lugar.",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.textSecundario,
                )
                TextButton(onClick = onAnular) { Text("Anular esta entrega", color = Colores.peligro) }
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

private fun diaSemana(ordinal: Int): String = when (ordinal) {
    0 -> "Lunes"; 1 -> "Martes"; 2 -> "Miércoles"; 3 -> "Jueves"; 4 -> "Viernes"; 5 -> "Sábado"; else -> "Domingo"
}

private fun mesLargo(mes: Int): String = when (mes) {
    1 -> "enero"; 2 -> "febrero"; 3 -> "marzo"; 4 -> "abril"; 5 -> "mayo"; 6 -> "junio"
    7 -> "julio"; 8 -> "agosto"; 9 -> "setiembre"; 10 -> "octubre"; 11 -> "noviembre"; else -> "diciembre"
}
