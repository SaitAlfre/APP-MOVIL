package pe.ecolecta.presentation.acopiador.registro

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Notes
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.ExpandMore
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.delay
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.ModalidadEntrega
import pe.ecolecta.domain.model.etiqueta
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EnlaceTexto
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.TipoBanner

private val PRESETS = listOf(10.0, 20.0, 30.0, 40.0)

@Composable
fun RegistroEntregaScreen(
    alGuardar: () -> Unit,
    alEscanearQr: () -> Unit,
    proveedorIdPreseleccionado: String? = null,
    // La clave incluye al proveedor: entrar desde la lista con otro proveedor debe empezar un
    // formulario limpio, no reutilizar el que quedó a medio llenar del anterior.
    viewModel: RegistroEntregaViewModel = koinViewModel(
        key = "registro-${proveedorIdPreseleccionado ?: "manual"}",
        parameters = { parametersOf(proveedorIdPreseleccionado) },
    ),
) {
    val estado by viewModel.uiState.collectAsState()
    var cambiandoProveedor by remember { mutableStateOf(proveedorIdPreseleccionado == null) }
    LaunchedEffect(viewModel) { viewModel.prepararNuevoRecojo() }

    LaunchedEffect(estado.guardadoExitoso) {
        if (estado.guardadoExitoso) {
            delay(if (estado.advertenciaDesviacion) 1400 else 800)
            viewModel.confirmarNavegacion()
            alGuardar()
        }
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        Column(
            Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.m),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            TarjetaProveedorSeleccionado(
                estado = estado,
                expandido = cambiandoProveedor,
                onAlternar = { cambiandoProveedor = !cambiandoProveedor },
            )

            if (cambiandoProveedor) {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                    BotonSecundario("Escanear QR de proveedor", alEscanearQr, icono = Icons.Filled.QrCodeScanner)
                    estado.proveedores.forEach { proveedor ->
                        FilaProveedor(
                            titulo = "${proveedor.codigo} - ${proveedor.nombres}",
                            detalle = proveedor.codigo,
                            seleccionado = proveedor.id == estado.proveedorId,
                            onClick = { viewModel.onProveedorCambia(proveedor.id); cambiandoProveedor = false },
                        )
                    }
                }
            }

            FormularioEntrega(estado = estado, viewModel = viewModel)

            Spacer(Modifier.height(Espaciado.l))
        }
    }
}

/**
 * Resumen del proveedor al que se le va a registrar la entrega: quién es, de qué zona, y si ya
 * tiene una entrega hoy (para no duplicarla por error). "Cambiar" despliega la lista completa de
 * la zona en vez de navegar a otra pantalla, porque el acopiador la usa parado y de un vistazo.
 */
@Composable
private fun TarjetaProveedorSeleccionado(estado: RegistroEntregaUiState, expandido: Boolean, onAlternar: () -> Unit) {
    val proveedor = estado.proveedorSeleccionado
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = Colores.brandContainer,
    ) {
        Column(Modifier.padding(Espaciado.m)) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "REGISTRO DE ACOPIO",
                    style = MaterialTheme.typography.labelMedium,
                    color = Colores.brandText,
                    fontWeight = FontWeight.Bold,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    modifier = Modifier.weight(1f, fill = false),
                )
                Spacer(Modifier.width(Espaciado.xs))
                EnlaceTexto(if (expandido) "Cerrar" else "Cambiar", onAlternar, color = Colores.brandText)
            }
            if (proveedor != null) {
                Spacer(Modifier.height(Espaciado.xxs))
                Text(proveedor.nombres, style = MaterialTheme.typography.titleLarge, color = Colores.onBrandContainer, maxLines = 3, overflow = TextOverflow.Ellipsis)
                Text(
                    "${proveedor.codigo} · ${estado.zonaNombre}",
                    style = MaterialTheme.typography.bodyMedium,
                    color = Colores.onBrandContainer,
                )
                if (estado.entregadoHoyDelSeleccionado) {
                    Spacer(Modifier.height(Espaciado.xs))
                    Text("Ya hay un recojo hoy. Al guardar podrás confirmar si es otro recojo real.",
                        style = MaterialTheme.typography.bodySmall, color = Colores.advertencia)
                }
            } else {
                Spacer(Modifier.height(Espaciado.xxs))
                Text(
                    "Selecciona un proveedor de tu zona para continuar.",
                    style = MaterialTheme.typography.bodyMedium,
                    color = Colores.onBrandContainer,
                )
            }
        }
    }
}

/**
 * Tarjeta de proveedor seleccionable. Cuando está elegida se rellena en verde, no solo se
 * enmarca: el acopiador registra de pie y con el teléfono al sol, y un borde fino no se ve.
 */
@Composable
private fun FilaProveedor(titulo: String, detalle: String, seleccionado: Boolean, onClick: () -> Unit) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick)
            .then(
                if (seleccionado) Modifier.border(1.dp, Colores.brand, MaterialTheme.shapes.medium) else Modifier,
            ),
        shape = MaterialTheme.shapes.medium,
        color = if (seleccionado) Colores.brandContainer else Colores.surface,
        tonalElevation = 1.dp,
        shadowElevation = 1.dp,
    ) {
        Row(
            Modifier.fillMaxWidth().padding(Espaciado.m),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Row(
                Modifier.weight(1f, fill = false),
                horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Icon(
                    Icons.Filled.Person,
                    contentDescription = null,
                    tint = if (seleccionado) Colores.brandText else Colores.textSecundario,
                    modifier = Modifier.size(20.dp),
                )
                Column {
                    Text(
                        titulo,
                        style = MaterialTheme.typography.titleMedium,
                        color = if (seleccionado) Colores.onBrandContainer else Colores.textPrimary,
                        maxLines = 1,
                        overflow = TextOverflow.Ellipsis,
                    )
                    Text(detalle, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                }
            }
            if (seleccionado) {
                Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(20.dp))
            }
        }
    }
}

/**
 * Campos de litros/tachos/modalidad/observaciones + botones de guardado + diálogo de duplicado.
 * Extraído para que [RegistroEntregaScreen] (selección manual del proveedor) y la pantalla de
 * escaneo QR (proveedor ya identificado por el QR) compartan exactamente la misma lógica de
 * registro sin duplicar el ViewModel ni el caso de uso.
 */
@Composable
internal fun FormularioEntrega(estado: RegistroEntregaUiState, viewModel: RegistroEntregaViewModel) {
    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
        Surface(shape = MaterialTheme.shapes.large, color = Colores.surface, border = BorderStroke(1.dp, Colores.borde)) {
            Column(Modifier.padding(Espaciado.m), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                Text("Litros recibidos", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    IconButton(onClick = { viewModel.sumarLitros(-1.0) }, enabled = !estado.cargando) {
                        Icon(Icons.Filled.Remove, "Restar un litro", tint = Colores.brand)
                    }
                    Box(Modifier.weight(1f)) { CampoLitrosGrande(estado.litros, viewModel::onLitrosCambia) }
                    IconButton(onClick = { viewModel.sumarLitros(1.0) }, enabled = !estado.cargando) {
                        Icon(Icons.Filled.Add, "Sumar un litro", tint = Colores.brand)
                    }
                }
                Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                    PRESETS.forEach { preset ->
                        ChipCantidad("${preset.toInt()} L", Modifier.weight(1f)) { if (!estado.cargando) viewModel.aplicarPreset(preset) }
                    }
                }
            }
        }
        OutlinedTextField(
            value = estado.observaciones, onValueChange = viewModel::onObservacionesCambia,
            modifier = Modifier.fillMaxWidth(), label = { Text("Observación (opcional)") },
            placeholder = { Text("Ej. demora o novedad del recojo") },
            minLines = 2, maxLines = 4, enabled = !estado.cargando,
            shape = MaterialTheme.shapes.medium,
        )

        estado.error?.let { Banner(it, TipoBanner.ERROR) }
        if (estado.guardadoExitoso) Banner("Recojo guardado en este teléfono.", TipoBanner.EXITO)
        if (estado.guardadoExitoso && estado.advertenciaDesviacion) {
            Banner("Guardado. La cantidad se desvía bastante del promedio reciente de este proveedor.", TipoBanner.ADVERTENCIA)
        }

        BotonPrimario(
            texto = if (estado.cargando) "Guardando…" else estado.textoGuardar,
            onClick = { viewModel.guardar() },
            habilitado = estado.puedeGuardar && !estado.cargando && !estado.guardadoExitoso,
        )
        Text("Se guarda en este teléfono y se envía cuando haya conexión.",
            style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
    }

    estado.entregaDuplicada?.let { duplicada ->
        AlertDialog(
            onDismissRequest = viewModel::cerrarAvisoDuplicado,
            shape = MaterialTheme.shapes.large,
            title = { Text("Este proveedor ya tiene una entrega hoy") },
            text = { Text(if (duplicada.puedeSumar) "¿Quieres sumar esta cantidad a tu entrega existente o registrar un segundo recojo?"
                else "Hay otro recojo registrado hoy a las ${pe.ecolecta.presentation.design.formatearHoraAcopio(duplicada.entrega.registradoEn)}. Confirma solo si estás realizando un segundo recojo real. El registro anterior se conservará.") },
            confirmButton = { TextButton(onClick = viewModel::registrarAparte) { Text("Es un segundo recojo") } },
            dismissButton = {
                androidx.compose.foundation.layout.Row {
                    if (duplicada.puedeSumar) TextButton(onClick = viewModel::sumarADuplicada) { Text("Sumar al mío") }
                    TextButton(onClick = viewModel::cerrarAvisoDuplicado) { Text("Volver") }
                }
            },
        )
    }
}

/** Lectura grande de litros, el dato que más importa en esta pantalla: se edita a mano o con los atajos de abajo. */
@Composable
private fun CampoLitrosGrande(valor: String, onValorCambia: (String) -> Unit) {
    OutlinedTextField(
        value = valor,
        onValueChange = { nuevo -> val normalizado = nuevo.replace(',', '.'); if (normalizado.count { c -> c == '.' } <= 1 && normalizado.all { c -> c.isDigit() || c == '.' }) onValorCambia(normalizado) },
        modifier = Modifier.fillMaxWidth(),
        placeholder = { Text("0.0", style = MaterialTheme.typography.headlineSmall, color = Colores.textSecundario) },
        textStyle = MaterialTheme.typography.headlineSmall.copy(textAlign = TextAlign.End, color = Colores.textPrimary),
        suffix = { Text("L", style = MaterialTheme.typography.titleMedium, color = Colores.textSecundario) },
        singleLine = true,
        shape = MaterialTheme.shapes.medium,
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
    )
}

/** Atajo de cantidad para sumar litros rápido (+0.5/+1/+5/+10), sin tener que escribir. */
@Composable
private fun ChipCantidad(texto: String, modifier: Modifier = Modifier, onClick: () -> Unit) {
    Surface(
        modifier = modifier.height(44.dp).clickable(onClick = onClick),
        shape = MaterialTheme.shapes.medium,
        color = Colores.brandContainer,
    ) {
        Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            Text(texto, style = MaterialTheme.typography.titleSmall, color = Colores.onBrandContainer, fontWeight = FontWeight.Bold)
        }
    }
}

/** Contador de tachos con +/- : más rápido de tocar que abrir el teclado para un número tan chico. */
@Composable
private fun SelectorTachos(valor: String, onSumar: (Int) -> Unit) {
    Surface(
        modifier = Modifier.fillMaxWidth().height(52.dp),
        shape = MaterialTheme.shapes.medium,
        color = Colores.surface,
        border = BorderStroke(1.dp, Colores.borde),
    ) {
        Row(Modifier.fillMaxSize().padding(horizontal = Espaciado.xs), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
            IconButton(onClick = { onSumar(-1) }) { Icon(Icons.Filled.Remove, contentDescription = "Restar tacho", tint = Colores.textSecundario) }
            Text(valor.ifBlank { "0" }, style = MaterialTheme.typography.titleLarge, color = Colores.textPrimary, fontWeight = FontWeight.Bold)
            IconButton(onClick = { onSumar(1) }) { Icon(Icons.Filled.Add, contentDescription = "Sumar tacho", tint = Colores.textSecundario) }
        }
    }
}

/** Cómo llegó la leche: quién trasladó los tachos hasta el punto de acopio. */
@Composable
private fun SelectorModalidad(valor: ModalidadEntrega, onCambia: (ModalidadEntrega) -> Unit) {
    var abierto by remember { mutableStateOf(false) }
    Box {
        Surface(
            modifier = Modifier.fillMaxWidth().height(52.dp).clickable { abierto = true },
            shape = MaterialTheme.shapes.medium,
            color = Colores.surface,
            border = BorderStroke(1.dp, Colores.borde),
        ) {
            Row(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.s),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Text(
                    valor.etiqueta(),
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.textPrimary,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    modifier = Modifier.weight(1f, fill = false),
                )
                Spacer(Modifier.width(Espaciado.xxs))
                Icon(Icons.Filled.ExpandMore, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.size(18.dp))
            }
        }
        DropdownMenu(expanded = abierto, onDismissRequest = { abierto = false }) {
            ModalidadEntrega.entries.forEach { opcion ->
                DropdownMenuItem(text = { Text(opcion.etiqueta()) }, onClick = { onCambia(opcion); abierto = false })
            }
        }
    }
}
