package pe.ecolecta.presentation.calidad

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Instant
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.calidad.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.design.*
import pe.ecolecta.presentation.navegacion.Pantalla

private enum class PestanaCalidad(val etiqueta: String, val icono: ImageVector) {
    INICIO("Inicio", Icons.Filled.Home),
    INSPECCIONES("Inspecciones", Icons.Filled.Assignment),
    NUEVA("Nueva prueba", Icons.Filled.AddCircle),
    HISTORIAL("Historial", Icons.Filled.History),
    PERFIL("Perfil", Icons.Filled.Person),
}

private val LUGARES = listOf("Campo", "Planta")

@Composable
fun CalidadShell(
    pantalla: Pantalla,
    onCambiarPantalla: (Pantalla) -> Unit,
    onCerrarSesion: () -> Unit,
    vm: CalidadViewModel = koinViewModel(),
) {
    val s by vm.uiState.collectAsState()
    var descartar by remember { mutableStateOf(false) }
    // El wizard de 4 pasos vive dentro de un solo paso FORMULARIO del ViewModel: subPaso/lugar son
    // solo UI y se reinician con cada borrador nuevo (cambia el id) para no arrastrar el paso 3 al
    // empezar otra prueba.
    var subPaso by rememberSaveable(s.borrador.id) { mutableStateOf(0) }
    var lugar by rememberSaveable(s.borrador.id) { mutableStateOf(LUGARES.first()) }
    CalidadBackHandler(s.paso != PasoCalidad.INICIO) {
        if (s.paso == PasoCalidad.FORMULARIO && subPaso > 0) subPaso-- else vm.volver()
    }

    val pestanaActual = when (s.paso) {
        PasoCalidad.INSPECCIONES -> PestanaCalidad.INSPECCIONES
        PasoCalidad.SELECCION, PasoCalidad.FORMULARIO, PasoCalidad.GUARDADO -> PestanaCalidad.NUEVA
        PasoCalidad.HISTORIAL -> PestanaCalidad.HISTORIAL
        PasoCalidad.DETALLE -> if (s.volverDetalle == PasoCalidad.HISTORIAL) PestanaCalidad.HISTORIAL else PestanaCalidad.INICIO
        PasoCalidad.PERFIL -> PestanaCalidad.PERFIL
        else -> PestanaCalidad.INICIO
    }
    val conBarraSuperior = s.paso == PasoCalidad.FORMULARIO || s.paso == PasoCalidad.DETALLE ||
        s.paso == PasoCalidad.HISTORIAL || s.paso == PasoCalidad.SELECCION

    Scaffold(
        containerColor = Colores.bgBase,
        topBar = {
            if (conBarraSuperior) {
                BarraSuperior(
                    titulo = s.paso.titulo,
                    alVolver = { if (s.paso == PasoCalidad.FORMULARIO && subPaso > 0) subPaso-- else vm.volver() },
                    accion = { PildoraPendientes(s.controles.count { it.syncState == SyncState.PENDING }) },
                )
            }
        },
        bottomBar = {
            CalidadBottomNav(pestanaActual = pestanaActual, habilitado = !s.guardando) { pestana ->
                when (pestana) {
                    PestanaCalidad.INICIO -> vm.navegar(PasoCalidad.INICIO)
                    PestanaCalidad.INSPECCIONES -> vm.navegar(PasoCalidad.INSPECCIONES)
                    PestanaCalidad.NUEVA -> vm.navegar(PasoCalidad.SELECCION)
                    PestanaCalidad.HISTORIAL -> vm.navegar(PasoCalidad.HISTORIAL)
                    PestanaCalidad.PERFIL -> vm.navegar(PasoCalidad.PERFIL)
                }
            }
        },
    ) { padding ->
        if (s.cargando) {
            Box(Modifier.padding(padding)) { IndicadorCarga() }
        } else key(s.paso) { EscenarioAnimado(s.paso) {
            LazyColumn(
                Modifier.fillMaxSize().padding(padding).imePadding().entradaPantalla(s.paso),
                contentPadding = PaddingValues(bottom = 20.dp),
                verticalArrangement = Arrangement.spacedBy(0.dp),
            ) {
                if (s.error != null || s.mensaje != null) item {
                    Column(Modifier.padding(horizontal = 20.dp, vertical = 12.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        s.error?.let { Banner(it, TipoBanner.ERROR) }
                        s.mensaje?.let { Banner(it, TipoBanner.INFO) }
                    }
                }
                when (s.paso) {
                    PasoCalidad.INICIO -> inicio(s, vm)
                    PasoCalidad.INSPECCIONES -> inspecciones(s, vm)
                    PasoCalidad.SELECCION -> seleccion(s, vm)
                    PasoCalidad.FORMULARIO -> formulario(s, vm, subPaso, { subPaso = it }, lugar, { lugar = it })
                    PasoCalidad.GUARDADO -> item { Guardado(vm) }
                    PasoCalidad.HISTORIAL -> historial(s, vm)
                    PasoCalidad.DETALLE -> detalle(s)
                    PasoCalidad.PERFIL -> perfil(s, onCerrarSesion)
                }
                if (s.paso.esFormulario) item {
                    TextButton(
                        onClick = { descartar = true },
                        enabled = !s.guardando,
                        modifier = Modifier.padding(horizontal = 12.dp),
                    ) { Text("Descartar análisis", color = Colores.peligro) }
                }
            }
        } }
    }
    if (descartar) AlertDialog(
        onDismissRequest = { descartar = false }, title = { Text("¿Descartar este análisis?") },
        text = { Text("Se perderán los datos que todavía no has guardado.") },
        confirmButton = { TextButton(onClick = { vm.descartar(); descartar = false }) { Text("Descartar") } },
        dismissButton = { TextButton(onClick = { descartar = false }) { Text("Continuar editando") } },
    )
    if (s.confirmarCambioProveedor) AlertDialog(
        onDismissRequest = vm::cancelarCambioProveedor, title = { Text("¿Cambiar de proveedor?") },
        text = { Text("Se perderán los resultados que ya escribiste o escaneaste para este proveedor.") },
        confirmButton = { TextButton(onClick = vm::confirmarCambioProveedor) { Text("Cambiar proveedor") } },
        dismissButton = { TextButton(onClick = vm::cancelarCambioProveedor) { Text("Continuar aquí") } },
    )
    if (s.escaneoPendiente != null) AlertDialog(
        onDismissRequest = vm::descartarEscaneo, title = { Text("¿Reemplazar los datos ya escritos?") },
        text = { Text("Ya hay resultados escritos en el formulario. El nuevo escaneo puede reemplazarlos.") },
        confirmButton = { TextButton(onClick = vm::confirmarEscaneo) { Text("Reemplazar") } },
        dismissButton = { TextButton(onClick = vm::descartarEscaneo) { Text("Conservar lo escrito") } },
    )
}

@Composable
private fun CalidadBottomNav(pestanaActual: PestanaCalidad, habilitado: Boolean, onSeleccionar: (PestanaCalidad) -> Unit) {
    val pestanas = PestanaCalidad.entries
    BarraNavegacionInferior(
        items = pestanas.map { ItemNavegacion(it.etiqueta, it.icono) },
        seleccionado = pestanas.indexOf(pestanaActual),
        onSeleccionar = { onSeleccionar(pestanas[it]) },
        habilitado = habilitado,
    )
}

// ---------------------------------------------------------------------------------------------
// Fechas: hoy, y la prioridad de una inspección pendiente
// ---------------------------------------------------------------------------------------------

private fun mismDia(epochA: Long, epochB: Long): Boolean {
    val zona = TimeZone.currentSystemDefault()
    return Instant.fromEpochMilliseconds(epochA).toLocalDateTime(zona).date ==
        Instant.fromEpochMilliseconds(epochB).toLocalDateTime(zona).date
}

private fun CalidadUiState.controlesDeHoy() = controles.filter { mismDia(it.registradoEn, ahora) }

/** Proveedores activos que hoy todavía no tienen un control registrado por este técnico. */
private fun CalidadUiState.pendientesDeHoy(): List<Proveedor> {
    val atendidosHoy = controlesDeHoy().map { it.proveedorId }.toSet()
    return proveedores.filter { it.estado == EstadoProveedor.ACTIVO && it.id !in atendidosHoy }
}

/** "Alta" si el último control de ese proveedor no quedó aprobado (o nunca se le hizo uno). */
private fun CalidadUiState.esPrioridadAlta(proveedorId: String): Boolean {
    val ultimo = controles.filter { it.proveedorId == proveedorId }.maxByOrNull { it.registradoEn }
    return ultimo == null || ultimo.estado != EstadoControlCalidad.APROBADO
}

// ---------------------------------------------------------------------------------------------
// Inicio
// ---------------------------------------------------------------------------------------------

private fun LazyListScope.inicio(s: CalidadUiState, vm: CalidadViewModel) {
    item { EncabezadoCalidad(tecnico = s.tecnico, pendientesSync = s.controles.count { it.syncState == SyncState.PENDING }) }
    item {
        Column(Modifier.padding(horizontal = 20.dp, vertical = 16.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            val hoy = s.controlesDeHoy()
            val pendientes = s.pendientesDeHoy()
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaEstadisticaCalidad("Asignadas hoy", (hoy.size + pendientes.size).toString(), Modifier.weight(1f))
                TarjetaEstadisticaCalidad("Registradas", hoy.size.toString(), Modifier.weight(1f))
            }
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                TarjetaEstadisticaCalidad("Sin sincronizar", s.controles.count { it.syncState == SyncState.PENDING }.toString(), Modifier.weight(1f), Colores.advertencia)
                TarjetaEstadisticaCalidad("Alertas", hoy.count { it.estado != EstadoControlCalidad.APROBADO }.toString(), Modifier.weight(1f), Colores.peligro)
            }

            Text("INSPECCIONES PENDIENTES", style = MaterialTheme.typography.labelLarge, color = Colores.textSecundario)
            if (pendientes.isEmpty()) {
                EstadoVacio("Ya registraste a todos tus proveedores de hoy", icono = Icons.Filled.TaskAlt)
            } else {
                pendientes.take(3).forEach { p -> TarjetaInspeccionPendiente(p, s.esPrioridadAlta(p.id)) { vm.iniciarPara(p) } }
                if (pendientes.size > 3) {
                    TextButton(onClick = { vm.navegar(PasoCalidad.INSPECCIONES) }) { Text("Ver las ${pendientes.size} pendientes") }
                }
            }

            BotonPrimarioCalidad("+ Nueva prueba LactoScan", onClick = { vm.navegar(PasoCalidad.SELECCION) })
        }
    }
}

/** Arranca directamente el formulario con este proveedor, sin pasar por el buscador de zona/proveedor. */
private fun CalidadViewModel.iniciarPara(p: Proveedor) {
    nuevo(); zona(p.zonaId); proveedor(p.id)
}

@Composable
private fun EncabezadoCalidad(tecnico: String, pendientesSync: Int) {
    Surface(color = CalidadMorado, modifier = Modifier.fillMaxWidth()) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 20.dp),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.Top,
        ) {
            Column(Modifier.weight(1f, fill = false)) {
                Text("Técnico de calidad", style = MaterialTheme.typography.bodyMedium, color = Color.White.copy(alpha = 0.85f))
                Text(tecnico.ifBlank { "Técnico" }, style = MaterialTheme.typography.headlineSmall, color = Color.White, maxLines = 1, overflow = TextOverflow.Ellipsis)
            }
            if (pendientesSync > 0) {
                Spacer(Modifier.width(12.dp))
                Surface(shape = RoundedCornerShape(50), color = Color.White) {
                    Text(
                        "$pendientesSync pendientes",
                        style = MaterialTheme.typography.labelMedium,
                        color = CalidadMorado,
                        fontWeight = FontWeight.Bold,
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                    )
                }
            }
        }
    }
}

@Composable
private fun TarjetaEstadisticaCalidad(etiqueta: String, valor: String, modifier: Modifier = Modifier, color: Color = Colores.textPrimary) {
    Surface(modifier = modifier.fillMaxWidth(), shape = MaterialTheme.shapes.medium, color = Colores.surface, tonalElevation = 1.dp, shadowElevation = 1.dp) {
        Column(Modifier.padding(16.dp)) {
            Text(valor, style = MaterialTheme.typography.headlineMedium, color = color, fontWeight = FontWeight.Bold)
            Text(etiqueta, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
        }
    }
}

@Composable
private fun TarjetaInspeccionPendiente(p: Proveedor, prioridadAlta: Boolean, alIniciar: () -> Unit) {
    Tarjeta {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f, fill = false)) {
                Text(p.codigo, style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                Text(p.nombres, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis)
                ChipEstado(if (prioridadAlta) "Alta" else "Normal", if (prioridadAlta) Colores.peligro else Colores.textSecundario, mostrarPunto = false)
            }
            Spacer(Modifier.width(12.dp))
            Button(onClick = alIniciar, colors = ButtonDefaults.buttonColors(containerColor = CalidadMorado, contentColor = Color.White)) {
                Text("Iniciar")
            }
        }
    }
}

// ---------------------------------------------------------------------------------------------
// Inspecciones (pendientes + completadas)
// ---------------------------------------------------------------------------------------------

private fun LazyListScope.inspecciones(s: CalidadUiState, vm: CalidadViewModel) {
    val pendientes = s.pendientesDeHoy()
    val completadas = s.controlesDeHoy()
    item {
        Text(
            "${pendientes.size} pendientes",
            style = MaterialTheme.typography.bodyMedium,
            color = Colores.textSecundario,
            modifier = Modifier.padding(horizontal = 20.dp, vertical = 12.dp),
        )
    }
    item { Text("PENDIENTES", style = MaterialTheme.typography.labelLarge, color = Colores.textSecundario, modifier = Modifier.padding(horizontal = 20.dp)) }
    if (pendientes.isEmpty()) {
        item { EstadoVacio("No tienes inspecciones pendientes", modifier = Modifier.padding(20.dp)) }
    } else {
        items(pendientes, key = { "p-" + it.id }) { p ->
            Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) { TarjetaInspeccionPendiente(p, s.esPrioridadAlta(p.id)) { vm.iniciarPara(p) } }
        }
    }
    item { Spacer(Modifier.height(12.dp)) }
    item { Text("COMPLETADAS", style = MaterialTheme.typography.labelLarge, color = Colores.textSecundario, modifier = Modifier.padding(horizontal = 20.dp)) }
    if (completadas.isEmpty()) {
        item { EstadoVacio("Todavía no registraste controles hoy", modifier = Modifier.padding(20.dp)) }
    } else {
        items(completadas, key = { "c-" + it.id }) { c ->
            Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) {
                Tarjeta(onClick = { vm.detalle(c.id) }) {
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                        Column(Modifier.weight(1f, fill = false)) {
                            Text(c.codigoMuestra, style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                            Text(
                                c.visita.proveedorNombre.ifBlank { s.proveedores.firstOrNull { it.id == c.proveedorId }?.nombres.orEmpty() },
                                style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis,
                            )
                            Text(c.visita.zonaNombre, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                        }
                        ChipEstado("✓ Completada", Colores.exito, mostrarPunto = false)
                    }
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------------------------
// Selección de zona y proveedor (entrada a "Nueva prueba" sin proveedor preseleccionado)
// ---------------------------------------------------------------------------------------------

private fun LazyListScope.seleccion(s: CalidadUiState, vm: CalidadViewModel) {
    item {
        Column(Modifier.padding(horizontal = 20.dp, vertical = 16.dp)) {
            Text(if (s.borrador.zonaId.isBlank()) "¿A qué zona te diriges?" else s.zona?.nombre.orEmpty(), style = MaterialTheme.typography.headlineSmall)
            Text("Selecciona la zona y toca el proveedor para continuar.", color = Colores.textSecundario)
        }
    }
    if (s.borrador.zonaId.isBlank()) {
        if (s.zonas.isEmpty()) item { EstadoVacio("No hay zonas activas", descripcion = "Registra una zona desde la administración móvil.", modifier = Modifier.padding(20.dp)) }
        items(s.zonas, key = { it.id }) { z ->
            Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) {
                Tarjeta(onClick = { vm.zona(z.id) }) {
                    Icon(Icons.Default.Groups, null, tint = CalidadMorado)
                    Text(z.nombre, style = MaterialTheme.typography.titleMedium, modifier = Modifier.padding(top = 8.dp))
                    Text("${s.proveedores.count { it.zonaId == z.id && it.estado == EstadoProveedor.ACTIVO }} proveedores", color = Colores.textSecundario)
                }
            }
        }
    } else {
        item {
            Column(Modifier.padding(horizontal = 20.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                TextButton(onClick = vm::volver, modifier = Modifier.padding(0.dp)) { Text("Cambiar zona") }
                CampoBusqueda(s.busqueda, vm::buscar, "Buscar por nombre o código")
            }
        }
        if (s.proveedoresFiltrados.isEmpty()) item { EstadoVacio("No hay proveedores que coincidan", descripcion = "Prueba otra búsqueda o selecciona otra zona.", modifier = Modifier.padding(20.dp)) }
        items(s.proveedoresFiltrados, key = { it.id }) { p ->
            Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) {
                Tarjeta(onClick = { vm.proveedor(p.id) }) {
                    Text(p.nombres, style = MaterialTheme.typography.titleMedium)
                    Text("Dueño: ${p.dueno ?: "No registrado"}", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                    Text(p.codigo, fontFamily = FontFamily.Monospace, color = CalidadMorado)
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------------------------
// Nueva prueba LactoScan: wizard de 4 pasos (Identificación · Resultados · Evidencia · Revisión)
// ---------------------------------------------------------------------------------------------

private val TITULOS_PASO = listOf("Identificación", "Resultados", "Evidencia", "Revisión")

private fun LazyListScope.formulario(
    s: CalidadUiState,
    vm: CalidadViewModel,
    subPaso: Int,
    onSubPasoCambia: (Int) -> Unit,
    lugar: String,
    onLugarCambia: (String) -> Unit,
) {
    item { StepperCalidad(subPaso) }
    when (subPaso) {
        0 -> pasoIdentificacion(s, vm, lugar, onLugarCambia) { onSubPasoCambia(1) }
        1 -> pasoResultados(s, vm, { onSubPasoCambia(0) }, { onSubPasoCambia(2) })
        2 -> pasoEvidencia(s, vm, { onSubPasoCambia(1) }, { onSubPasoCambia(3) })
        else -> pasoRevision(s, vm, { onSubPasoCambia(2) })
    }
}

@Composable
private fun StepperCalidad(subPaso: Int) {
    Row(Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 16.dp)) {
        TITULOS_PASO.forEachIndexed { i, titulo ->
            Column(Modifier.weight(1f), horizontalAlignment = Alignment.CenterHorizontally) {
                val completado = i < subPaso
                val activo = i == subPaso
                Box(
                    Modifier.size(28.dp).clip(CircleShape)
                        .background(if (completado || activo) CalidadMorado else Colores.surfaceAlta),
                    contentAlignment = Alignment.Center,
                ) {
                    if (completado) {
                        Icon(Icons.Filled.Check, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                    } else {
                        Text((i + 1).toString(), color = if (activo) Color.White else Colores.textSecundario, fontWeight = FontWeight.Bold)
                    }
                }
                Spacer(Modifier.height(4.dp))
                Text(
                    titulo,
                    style = MaterialTheme.typography.labelSmall,
                    color = if (activo) CalidadMorado else Colores.textSecundario,
                    maxLines = 1,
                    overflow = TextOverflow.Ellipsis,
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center,
                )
            }
        }
    }
}

private fun LazyListScope.pasoIdentificacion(s: CalidadUiState, vm: CalidadViewModel, lugar: String, onLugarCambia: (String) -> Unit, alSiguiente: () -> Unit) {
    val b = s.borrador
    item {
        Column(Modifier.padding(horizontal = 20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            Tarjeta {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                    Column(Modifier.weight(1f, fill = false)) {
                        Text("PROVEEDOR", style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                        Text(s.proveedor?.nombres.orEmpty(), style = MaterialTheme.typography.titleMedium, maxLines = 1, overflow = TextOverflow.Ellipsis)
                        Text(s.zona?.nombre.orEmpty(), style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                    }
                    TextButton(onClick = vm::cambiarProveedor, enabled = !s.guardando) { Text("Cambiar") }
                }
            }
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                CampoTexto(b.fecha, { vm.campo("fecha", it) }, "Fecha (dd/mm/aaaa)", modifier = Modifier.weight(1f))
                CampoTexto(b.hora, { vm.campo("hora", it) }, "Hora (hh:mm)", modifier = Modifier.weight(1f))
            }
            CampoTexto(b.serial, { vm.campo("serial", it) }, "Equipo LactoScan (serie)", iconoInicial = Icons.Filled.Science)

            Text("Lugar", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
            SegmentadoCalidad(LUGARES, lugar, onLugarCambia)

            BotonPrimarioCalidad("Siguiente", alSiguiente, habilitado = s.proveedor != null)
        }
    }
}

@Composable
private fun SegmentadoCalidad(opciones: List<String>, valor: String, onCambia: (String) -> Unit) {
    Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        opciones.forEach { opcion ->
            val seleccionado = opcion == valor
            Surface(
                modifier = Modifier.weight(1f).height(44.dp).clickable { onCambia(opcion) },
                shape = MaterialTheme.shapes.medium,
                color = if (seleccionado) CalidadMorado else Colores.surface,
                border = if (!seleccionado) androidx.compose.foundation.BorderStroke(1.dp, Colores.borde) else null,
            ) {
                Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                    Text(opcion, color = if (seleccionado) Color.White else Colores.textPrimary, fontWeight = FontWeight.SemiBold)
                }
            }
        }
    }
}

@Composable
private fun BotonPrimarioCalidad(texto: String, onClick: () -> Unit, habilitado: Boolean = true, cargando: Boolean = false) {
    Button(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().height(52.dp),
        enabled = habilitado && !cargando,
        shape = MaterialTheme.shapes.medium,
        colors = ButtonDefaults.buttonColors(containerColor = CalidadMorado, contentColor = Color.White),
    ) {
        if (cargando) CircularProgressIndicator(modifier = Modifier.size(22.dp), color = Color.White, strokeWidth = 2.dp)
        else Text(texto, style = MaterialTheme.typography.titleMedium)
    }
}

private fun LazyListScope.pasoResultados(s: CalidadUiState, vm: CalidadViewModel, alAnterior: () -> Unit, alSiguiente: () -> Unit) {
    val b = s.borrador
    item {
        Text(
            "Ingresa los valores del equipo LactoScan",
            style = MaterialTheme.typography.titleMedium,
            modifier = Modifier.padding(start = 20.dp, end = 20.dp, top = 4.dp, bottom = 8.dp),
        )
    }
    item {
        Column(Modifier.padding(horizontal = 20.dp)) {
            Tarjeta {
                Icon(Icons.Default.DocumentScanner, null, tint = CalidadMorado, modifier = Modifier.size(32.dp))
                Text(
                    "También puedes escanear el comprobante impreso del equipo en el siguiente paso.",
                    style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario, modifier = Modifier.padding(top = 8.dp),
                )
            }
        }
    }
    items(parametrosCalidad, key = { it.clave }) { p -> Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) { ParametroCampo(p, b, vm) } }
    item {
        Row(Modifier.padding(horizontal = 20.dp, vertical = 8.dp), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            OutlinedButton(onClick = alAnterior, modifier = Modifier.weight(1f).height(52.dp), shape = MaterialTheme.shapes.medium) { Text("Anterior") }
            Box(Modifier.weight(1f)) { BotonPrimarioCalidad("Siguiente", alSiguiente) }
        }
    }
}

private fun LazyListScope.pasoEvidencia(s: CalidadUiState, vm: CalidadViewModel, alAnterior: () -> Unit, alSiguiente: () -> Unit) {
    item {
        Column(Modifier.padding(horizontal = 20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            Tarjeta {
                Text(
                    "Coloca un solo comprobante dentro del encuadre, con buena iluminación.",
                    style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario, modifier = Modifier.padding(bottom = 8.dp),
                )
                EscanerComprobante(vm::escanear, vm::error)
            }
            CampoTexto(s.borrador.observaciones, { vm.campo("observaciones", it) }, "Observaciones adicionales (opcional)")
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedButton(onClick = alAnterior, modifier = Modifier.weight(1f).height(52.dp), shape = MaterialTheme.shapes.medium) { Text("Anterior") }
                Box(Modifier.weight(1f)) { BotonPrimarioCalidad("Siguiente", alSiguiente) }
            }
        }
    }
}

private fun LazyListScope.pasoRevision(s: CalidadUiState, vm: CalidadViewModel, alAnterior: () -> Unit) {
    val b = s.borrador
    item {
        Column(Modifier.padding(horizontal = 20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            Tarjeta {
                Text("RESUMEN DEL CONTROL", style = MaterialTheme.typography.labelLarge, color = Colores.textSecundario, modifier = Modifier.padding(bottom = 8.dp))
                parametrosCalidad.forEach { p ->
                    val valor = b.valores[p.clave]?.takeIf { it.isNotBlank() }
                    Row(Modifier.fillMaxWidth().padding(vertical = 6.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                        Text(p.nombre, color = Colores.textSecundario, modifier = Modifier.weight(1f, fill = false), maxLines = 1, overflow = TextOverflow.Ellipsis)
                        Spacer(Modifier.width(8.dp))
                        Text(
                            valor?.let { "$it ${if (p.clave == "congelacion") b.unidadCongelacion else p.unidad}".trim() } ?: "—",
                            fontWeight = FontWeight.Bold, color = Colores.textPrimary,
                        )
                    }
                }
            }
            Banner("La aplicación no aplica sanciones automáticamente. Los resultados quedan para revisión.", TipoBanner.INFO)
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                OutlinedButton(onClick = alAnterior, enabled = !s.guardando, modifier = Modifier.weight(1f).height(52.dp), shape = MaterialTheme.shapes.medium) { Text("Anterior") }
                Box(Modifier.weight(1f)) { BotonPrimarioCalidad(if (s.guardando) "Guardando..." else "Guardar control", vm::guardar, cargando = s.guardando) }
            }
        }
    }
}

@Composable
private fun ParametroCampo(p: ParametroCalidad, b: BorradorVisita, vm: CalidadViewModel) {
    val valor = b.valores[p.clave].orEmpty()
    val vacio = valor.isBlank()
    val invalido = !vacio && !b.formatoValido(p.clave)
    val dudoso = !vacio && !invalido && p.clave in b.dudosos
    val correcto = !vacio && !invalido && b.correcto(p.clave)
    val unidad = if (p.clave == "congelacion") b.unidadCongelacion else p.unidad
    Tarjeta {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f, fill = false)) {
                Text(p.nombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                Text("Rango: ${p.referencia}", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
            }
            Spacer(Modifier.width(12.dp))
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                OutlinedTextField(
                    value = valor,
                    onValueChange = { vm.campo(p.clave, it) },
                    singleLine = true,
                    isError = invalido,
                    textStyle = MaterialTheme.typography.titleMedium.copy(fontFamily = FontFamily.Monospace),
                    suffix = if (unidad.isNotBlank()) { { Text(unidad, style = MaterialTheme.typography.bodySmall) } } else null,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text),
                    modifier = Modifier.width(110.dp),
                )
                val colorPunto = when {
                    vacio -> Colores.borde
                    invalido -> Colores.peligro
                    dudoso -> Colores.info
                    correcto -> Colores.exito
                    else -> Colores.peligro
                }
                Box(Modifier.size(10.dp).clip(CircleShape).background(colorPunto))
            }
        }
        if (p.clave == "congelacion") Row(Modifier.padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf("°C", "°H").forEach { u -> FilterChip(selected = b.unidadCongelacion == u, onClick = { vm.campo("unidad", u) }, label = { Text(u) }) }
        }
        val textoAyuda = when {
            invalido -> if (permiteNegativo(p.clave)) "Usa solo números, con coma o punto decimal." else "Usa solo números positivos, con coma o punto decimal."
            dudoso -> "El comprobante trae más de un número en esta línea; verifica el valor."
            else -> null
        }
        textoAyuda?.let { Text(it, style = MaterialTheme.typography.bodySmall, color = if (invalido) Colores.peligro else Colores.info, modifier = Modifier.padding(top = 4.dp)) }
        if (p.clave == "agua" && (b.numero("agua") ?: 0.0) > 0) {
            Text("CRÍTICO · Posible agua añadida", color = Colores.peligro, style = MaterialTheme.typography.bodySmall, modifier = Modifier.padding(top = 4.dp))
        }
    }
}

@Composable
private fun Guardado(vm: CalidadViewModel) {
    Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
        Banner("Análisis guardado correctamente.", TipoBanner.EXITO)
        BotonPrimarioCalidad("Registrar otro análisis", onClick = { vm.navegar(PasoCalidad.SELECCION) })
        BotonSecundario("Ver historial", { vm.navegar(PasoCalidad.HISTORIAL) }, icono = Icons.Default.History)
    }
}

// ---------------------------------------------------------------------------------------------
// Historial
// ---------------------------------------------------------------------------------------------

private fun LazyListScope.historial(s: CalidadUiState, vm: CalidadViewModel) {
    item {
        LazyRow(
            Modifier.padding(horizontal = 20.dp, vertical = 12.dp),
            horizontalArrangement = Arrangement.spacedBy(8.dp),
        ) {
            item { FilterChip(selected = s.filtro == null, onClick = { vm.filtrar(null) }, label = { Text("Todos") }) }
            items(listOf(EstadoControlCalidad.APROBADO, EstadoControlCalidad.OBSERVADO, EstadoControlCalidad.RECHAZADO)) { e ->
                FilterChip(selected = s.filtro == e, onClick = { vm.filtrar(e) }, label = { Text(if (e == EstadoControlCalidad.OBSERVADO) "Con observaciones" else e.etiqueta()) })
            }
        }
    }
    if (s.historial.isEmpty()) item { EstadoVacio("No hay análisis con este filtro", modifier = Modifier.padding(20.dp)) }
    items(s.historial, key = { it.id }) { c -> Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) { TarjetaHistorial(c, s) { vm.detalle(c.id) } } }
}

@Composable
private fun TarjetaHistorial(c: ControlCalidad, s: CalidadUiState, click: () -> Unit) {
    Tarjeta(onClick = click) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.Top) {
            Column(Modifier.weight(1f, fill = false)) {
                Text(formatearFecha(c.registradoEn), style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario)
                Text(
                    c.visita.proveedorNombre.ifBlank { s.proveedores.firstOrNull { it.id == c.proveedorId }?.nombres ?: "Proveedor no disponible" },
                    style = MaterialTheme.typography.titleMedium, maxLines = 1, overflow = TextOverflow.Ellipsis,
                )
                val ph = c.ph?.let { "pH $it" }
                val grasa = c.grasa?.let { "Grasa $it%" }
                Text(listOfNotNull(ph, grasa).joinToString(" · "), style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
            }
            Spacer(Modifier.width(12.dp))
            Column(horizontalAlignment = Alignment.End) {
                ChipEstado(if (c.estado == EstadoControlCalidad.APROBADO) "Aceptable" else "Revisar", c.estado.color(), mostrarPunto = false)
                Spacer(Modifier.height(6.dp))
                ChipSyncCalidad(c.syncState)
            }
        }
    }
}

/** El [ChipSync] compartido espera una [pe.ecolecta.domain.model.Entrega]; para un control de calidad
 * basta con su propio [SyncState], así que se arma una pastilla equivalente sin depender de ese tipo. */
@Composable
private fun ChipSyncCalidad(estado: SyncState) {
    val (texto, color) = when (estado) {
        SyncState.SYNCED -> "✓ Sincronizado" to Colores.exito
        SyncState.PENDING -> "⏳ Pendiente" to Colores.advertencia
        SyncState.SYNCING -> "Sincronizando…" to Colores.info
        SyncState.ERROR -> "Error" to Colores.peligro
        SyncState.CONFLICT -> "Conflicto" to Colores.peligro
    }
    ChipEstado(texto, color, mostrarPunto = false)
}

// ---------------------------------------------------------------------------------------------
// Detalle
// ---------------------------------------------------------------------------------------------

private fun LazyListScope.detalle(s: CalidadUiState) {
    val c = s.detalle
    if (c == null) { item { EstadoVacio("El análisis no está disponible", modifier = Modifier.padding(20.dp)) }; return }
    val v = c.visita
    item {
        Column(Modifier.padding(horizontal = 20.dp, vertical = 16.dp)) {
            Text(v.proveedorNombre.ifBlank { s.proveedores.firstOrNull { it.id == c.proveedorId }?.nombres.orEmpty() }, style = MaterialTheme.typography.headlineSmall)
            Text(v.zonaNombre.ifBlank { s.zonas.firstOrNull { it.id == v.zonaId }?.nombre.orEmpty() }, color = Colores.textSecundario)
            Text(formatearFechaHora(c.registradoEn), color = Colores.textSecundario)
            ChipEstado(c.estado.etiqueta(), c.estado.color(), modifier = Modifier.padding(top = 8.dp))
        }
    }
    item {
        Box(Modifier.padding(horizontal = 20.dp)) {
            Tarjeta {
                Text("Trazabilidad", style = MaterialTheme.typography.titleMedium)
                Dato("Proveedor", v.proveedorNombre.ifBlank { "No disponible" })
                Dato("Zona", v.zonaNombre.ifBlank { "No disponible" })
                Dato("Técnico", v.tecnicoNombre.ifBlank { "No disponible" })
                c.serialAnalizador?.takeIf { it.isNotBlank() }?.let { Dato("Equipo LactoScan (serie)", it) }
                Dato("Origen", if (c.origenCaptura == OrigenCaptura.ESCANER) "Comprobante escaneado" else "Ingreso manual")
                c.observaciones?.let { Dato("Observaciones", it) }
            }
        }
    }
    item { Text("Parámetros del analizador", style = MaterialTheme.typography.titleLarge, modifier = Modifier.padding(start = 20.dp, end = 20.dp, top = 12.dp)) }
    items(parametrosCalidad, key = { it.clave }) { p ->
        val valor = c.valores()[p.clave]
        val conocido = v.referencias.containsKey(p.clave)
        val ok = if (conocido) p.clave !in v.parametrosAlertados && valor != null else p.correcto(valor)
        Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) {
            Tarjeta {
                Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                    Text(p.nombre, modifier = Modifier.weight(1f))
                    Text(
                        valor?.toString() ?: "Sin dato", fontFamily = FontFamily.Monospace,
                        color = if (valor == null) Colores.textSecundario else if (ok) Colores.exito else Colores.peligro,
                    )
                }
                Text(v.referencias[p.clave] ?: "Referencia actual: ${p.referencia}", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
            }
        }
    }
    c.textoComprobante?.let { texto ->
        item {
            Box(Modifier.padding(horizontal = 20.dp, vertical = 6.dp)) {
                Tarjeta {
                    Text("Texto original del comprobante", style = MaterialTheme.typography.titleMedium)
                    Text(texto, fontFamily = FontFamily.Monospace, style = MaterialTheme.typography.bodySmall)
                }
            }
        }
    }
}

// ---------------------------------------------------------------------------------------------
// Perfil
// ---------------------------------------------------------------------------------------------

private fun LazyListScope.perfil(s: CalidadUiState, onCerrarSesion: () -> Unit) {
    item {
        Surface(color = CalidadMorado, modifier = Modifier.fillMaxWidth()) {
            Row(Modifier.fillMaxWidth().padding(20.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                Box(Modifier.size(56.dp).background(Color.White.copy(alpha = 0.18f), CircleShape), contentAlignment = Alignment.Center) {
                    Icon(Icons.Filled.Person, contentDescription = null, tint = Color.White, modifier = Modifier.size(30.dp))
                }
                Column {
                    Text(s.tecnico.ifBlank { "Técnico" }, style = MaterialTheme.typography.headlineSmall, color = Color.White)
                    Text("Técnico de calidad", style = MaterialTheme.typography.bodyMedium, color = Color.White.copy(alpha = 0.85f))
                }
            }
        }
    }
    item {
        Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp)) {
            Tarjeta {
                Dato("Controles registrados", s.controles.size.toString())
                DivisorSutil(Modifier.padding(vertical = 8.dp))
                Dato("Pendientes por sincronizar", s.controles.count { it.syncState == SyncState.PENDING }.toString())
            }
            BotonBorde("Cerrar sesión", Colores.peligro, onCerrarSesion, icono = Icons.AutoMirrored.Filled.Logout)
        }
    }
}

internal fun EstadoControlCalidad.etiqueta() = when (this) {
    EstadoControlCalidad.APROBADO -> "Aprobado"
    EstadoControlCalidad.OBSERVADO -> "Aprobado con observaciones"
    EstadoControlCalidad.RECHAZADO -> "Rechazado"
    EstadoControlCalidad.REPETIR -> "Requiere repetir análisis"
}
@Composable internal fun EstadoControlCalidad.color() = when (this) {
    EstadoControlCalidad.APROBADO -> Colores.exito
    EstadoControlCalidad.OBSERVADO -> Colores.advertencia
    EstadoControlCalidad.RECHAZADO -> Colores.peligro
    EstadoControlCalidad.REPETIR -> Colores.info
}
@Composable private fun Dato(nombre: String, valor: String) {
    Text(nombre, style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario, modifier = Modifier.padding(top = 10.dp))
    Text(valor.ifBlank { "No registrado" })
}
@Composable expect fun CalidadBackHandler(enabled: Boolean, onBack: () -> Unit)
