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
import androidx.compose.material.icons.filled.Assignment
import androidx.compose.material.icons.filled.EventAvailable
import androidx.compose.material.icons.filled.EventBusy
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
import pe.ecolecta.domain.model.EstadoSeguimiento
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
import pe.ecolecta.presentation.seguimiento.rememberSolicitadorPermisoUbicacion

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
            if (!estado.jornadaAbierta) {
                TarjetaSinJornada(alAbrirJornada)
            } else {
                TarjetaJornadaActiva(estado)

                TarjetaSeguimiento(
                    estadoSeguimiento = estado.estadoSeguimiento,
                    mostrarAvisoPermisoDenegado = estado.mostrarAvisoPermisoDenegado,
                    onPermisoUbicacionResultado = viewModel::onPermisoUbicacionResultado,
                    onDetener = viewModel::detenerSeguimiento,
                    onDescartarAviso = viewModel::descartarAvisoPermiso,
                )

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
                            onClick = if (puedeEditar(entrega)) ({ entregaParaCorregir = entrega }) else null,
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
private fun TarjetaSeguimiento(
    estadoSeguimiento: EstadoSeguimiento,
    mostrarAvisoPermisoDenegado: Boolean,
    onPermisoUbicacionResultado: (Boolean) -> Unit,
    onDetener: () -> Unit,
    onDescartarAviso: () -> Unit,
) {
    val solicitarPermiso = rememberSolicitadorPermisoUbicacion(onResultado = onPermisoUbicacionResultado)
    val activo = estadoSeguimiento == EstadoSeguimiento.ACTIVO ||
        estadoSeguimiento == EstadoSeguimiento.BUSCANDO ||
        estadoSeguimiento == EstadoSeguimiento.SIN_SENAL ||
        estadoSeguimiento == EstadoSeguimiento.SIN_CONEXION
    val (textoEstado, colorEstado) = textoYColorSeguimiento(estadoSeguimiento)

    Tarjeta {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Text(
                "Seguimiento de ubicación",
                style = MaterialTheme.typography.titleMedium,
                color = Colores.textPrimary,
                maxLines = 1,
                overflow = TextOverflow.Ellipsis,
                modifier = Modifier.weight(1f, fill = false),
            )
            Spacer(Modifier.width(Espaciado.s))
            ChipEstado(textoEstado, colorEstado, mostrarPunto = false)
        }
        Spacer(Modifier.height(Espaciado.xxs))
        Text(
            if (activo) {
                "Tu ubicación se está registrando."
            } else {
                "Los proveedores de tu zona podrán ver tu ubicación mientras dure la jornada."
            },
            style = MaterialTheme.typography.bodyMedium,
            color = Colores.textSecundario,
        )
        Spacer(Modifier.height(Espaciado.s))
        AccionTenue(
            texto = if (activo) "Detener seguimiento" else "Iniciar seguimiento",
            onClick = if (activo) onDetener else solicitarPermiso,
        )

        if (mostrarAvisoPermisoDenegado) {
            Spacer(Modifier.height(Espaciado.s))
            Banner(
                mensaje = "Permiso de ubicación rechazado. Puedes activarlo desde los Ajustes del sistema; " +
                    "el registro de entregas sigue funcionando igual sin el seguimiento.",
                tipo = TipoBanner.ADVERTENCIA,
            )
            Spacer(Modifier.height(Espaciado.xxs))
            TextButton(onClick = onDescartarAviso) { Text("Entendido") }
        }
    }
}

/**
 * Acción secundaria dentro de una tarjeta: pastilla de fondo tenue, a ras del contenido. No usa
 * un botón de ancho completo a propósito — ese competiría con las acciones principales de la pantalla.
 */
@Composable
private fun AccionTenue(texto: String, onClick: () -> Unit) {
    Surface(
        shape = MaterialTheme.shapes.small,
        color = Colores.brandContainer,
        contentColor = Colores.onBrandContainer,
        modifier = Modifier.clickable(onClick = onClick),
    ) {
        Text(
            texto,
            style = MaterialTheme.typography.labelLarge,
            modifier = Modifier.padding(horizontal = Espaciado.m, vertical = Espaciado.xs),
        )
    }
}

@Composable
private fun textoYColorSeguimiento(estado: EstadoSeguimiento): Pair<String, Color> = when (estado) {
    EstadoSeguimiento.INACTIVO -> "Detenido" to Colores.textSecundario
    EstadoSeguimiento.BUSCANDO -> "Buscando ubicación…" to Colores.info
    EstadoSeguimiento.ACTIVO -> "Activo" to Colores.exito
    EstadoSeguimiento.SIN_CONEXION -> "Sin conexión" to Colores.advertencia
    EstadoSeguimiento.SIN_SENAL -> "Sin señal GPS" to Colores.advertencia
    EstadoSeguimiento.PERMISO_DENEGADO -> "Permiso denegado" to Colores.peligro
    EstadoSeguimiento.ERROR_ALMACENAMIENTO -> "Error al guardar" to Colores.peligro
    EstadoSeguimiento.ERROR_CAPTURA -> "Error de ubicación" to Colores.peligro
    EstadoSeguimiento.NO_DISPONIBLE_PLATAFORMA -> "No disponible" to Colores.textSecundario
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
