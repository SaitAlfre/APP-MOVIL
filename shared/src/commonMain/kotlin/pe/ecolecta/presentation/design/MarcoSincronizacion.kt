package pe.ecolecta.presentation.design

import androidx.compose.animation.core.*
import androidx.compose.foundation.clickable
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.rotate
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import org.koin.compose.koinInject
import pe.ecolecta.domain.usecase.sync.CoordinadorSync

/** Una barra con insets consumidos evita superponer el reloj y duplicar el margen de cada pantalla. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MarcoSincronizacion(visible: Boolean, permiteGesto: Boolean, contenido: @Composable () -> Unit) {
    if (!visible) { contenido(); return }
    val sync = koinInject<CoordinadorSync>()
    val estado by sync.estado.collectAsState()
    val scope = rememberCoroutineScope()
    val snackbar = remember { SnackbarHostState() }
    var detalles by remember { mutableStateOf(false) }
    var enlazar by remember { mutableStateOf(false) }
    var pin by remember { mutableStateOf("") }
    var vinculando by remember { mutableStateOf(false) }
    var mensaje by remember { mutableStateOf<String?>(null) }
    fun sincronizar() {
        if (estado.requiereEnlace) { enlazar = true; return }
        scope.launch { sync.ejecutar(manual = true)?.let { mensaje = it } }
    }
    LaunchedEffect(mensaje) {
        val texto = mensaje ?: return@LaunchedEffect
        val resultado = snackbar.showSnackbar(texto,
            actionLabel = if (estado.requiereEnlace) "Vincular" else if (estado.sinConexion || estado.problema != null) "Reintentar" else null,
            withDismissAction = true)
        mensaje = null
        if (resultado == SnackbarResult.ActionPerformed) sincronizar()
    }
    LaunchedEffect(sync) {
        while (true) {
            try { sync.actualizarPendientes() } catch (e: CancellationException) { throw e } catch (_: Exception) { }
            delay(2000)
        }
    }
    val color = when {
        estado.sincronizando -> Color(0xFF1976D2)
        estado.problema != null || estado.errores > 0 -> Colores.peligro
        estado.sinConexion -> Colores.textSecundario
        estado.novedades -> Color(0xFF6254B5)
        estado.pendientes > 0 -> Colores.textPrimary
        estado.verificado -> Colores.exito
        else -> Colores.textSecundario
    }
    Scaffold(
        containerColor = Colores.bgBase,
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
        snackbarHost = { SnackbarHost(snackbar, Modifier.navigationBarsPadding()) },
        topBar = {
            TopAppBar(
                title = {
                    Column(Modifier.clickable { detalles = true }) {
                        Text("Ecolecta", style = MaterialTheme.typography.titleLarge)
                        Text(estado.texto, style = MaterialTheme.typography.labelSmall, color = color,
                            maxLines = 1, overflow = TextOverflow.Ellipsis)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Colores.bgBase, titleContentColor = Colores.textPrimary),
                actions = {
                    IconButton(onClick = ::sincronizar, enabled = !estado.sincronizando && !vinculando) {
                        BadgedBox(badge = {
                            if (!estado.sincronizando && (estado.pendientes > 0 || estado.errores > 0 || estado.requiereEnlace))
                                Badge(containerColor = Colores.peligro) {
                                    Text(if (estado.requiereEnlace) "!" else (estado.pendientes + estado.errores).coerceAtMost(99).toString())
                                }
                        }) {
                            val angulo = if (estado.sincronizando) {
                                val animacion = rememberInfiniteTransition(label = "Sincronización")
                                val giro by animacion.animateFloat(0f, 360f,
                                    infiniteRepeatable(tween(1000, easing = LinearEasing)), label = "Giro")
                                giro
                            } else 0f
                            // La acción siempre conserva sus dos flechas; el texto explica cualquier fallo.
                            Icon(Icons.Default.Sync, "Sincronizar ahora. ${estado.texto}",
                                Modifier.size(24.dp).rotate(angulo), tint = color)
                        }
                    }
                    IconButton(onClick = { detalles = true }) {
                        Icon(Icons.Default.MoreVert, "Detalles de sincronización", tint = Colores.textPrimary)
                    }
                },
            )
        },
    ) { padding ->
        Box(Modifier.fillMaxSize().padding(padding).consumeWindowInsets(padding)) {
            if (permiteGesto) PullToRefreshBox(estado.sincronizando, ::sincronizar, Modifier.fillMaxSize()) { contenido() }
            else contenido()
        }
    }
    if (detalles) AlertDialog(
        onDismissRequest = { detalles = false },
        title = { Text("Sincronización con la web") },
        text = {
            Column(Modifier.heightIn(max = 420.dp).verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text("Cuenta: ${estado.cuenta}")
                Text(estado.servidor, style = MaterialTheme.typography.bodySmall)
                Text("${estado.pendientes} cambios por enviar · ${estado.errores} por revisar")
                if (estado.otrosPendientes > 0) Text("Hay ${estado.otrosPendientes} registros de otras cuentas en este teléfono. Se conservan; cada cuenta envía sus propios cambios.", style = MaterialTheme.typography.bodySmall)
                Text(if (estado.novedades) "Hay datos en la nube por descargar" else "Las novedades se comprueban al conectar con el panel")
                Text(estado.ultimaSync?.let { "Última sincronización completa: ${formatearFechaHora(it)}" } ?: "Aún no hay una sincronización completa confirmada")
                estado.problema?.let { Text(it, color = Colores.peligro) }
                Row {
                    Text("Sincronizar automáticamente", Modifier.weight(1f))
                    Switch(estado.automatico, sync::automatico, enabled = !estado.sincronizando)
                }
                TextButton(onClick = { detalles = false; enlazar = true }) { Text("Vincular cuenta con la web") }
                Text("Sin internet puedes seguir trabajando. No se borran tus cambios al reintentar.", style = MaterialTheme.typography.bodySmall)
            }
        },
        confirmButton = { TextButton(onClick = { detalles = false; sincronizar() }, enabled = !estado.sincronizando) { Text("Sincronizar ahora") } },
        dismissButton = { TextButton(onClick = { detalles = false }) { Text("Cerrar") } },
    )
    if (enlazar) AlertDialog(
        onDismissRequest = { if (!vinculando) { enlazar = false; pin = "" } },
        title = { Text("Vincular ${estado.cuenta}") },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                Text("Ingresa el PIN de esta cuenta en el panel web. No se guarda el PIN ni se borran tus datos locales.")
                OutlinedTextField(value = pin, onValueChange = { pin = it.take(60) }, label = { Text("PIN del panel web") },
                    visualTransformation = PasswordVisualTransformation(), singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword), enabled = !vinculando)
                if (vinculando) LinearProgressIndicator(Modifier.fillMaxWidth())
            }
        },
        confirmButton = {
            TextButton(enabled = pin.isNotBlank() && !vinculando, onClick = {
                scope.launch {
                    vinculando = true
                    try { mensaje = sync.vincular(pin) } finally { pin = ""; vinculando = false; enlazar = false }
                }
            }) { Text("Vincular y sincronizar") }
        },
        dismissButton = { TextButton(enabled = !vinculando, onClick = { enlazar = false; pin = "" }) { Text("Cancelar") } },
    )
}
