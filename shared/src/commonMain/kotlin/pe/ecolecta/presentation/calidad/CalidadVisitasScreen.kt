package pe.ecolecta.presentation.calidad

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.*
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import kotlin.time.Instant
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.calidad.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.design.*
import pe.ecolecta.presentation.navegacion.Pantalla

@Composable
fun CalidadShell(
    pantalla: Pantalla,
    onCambiarPantalla: (Pantalla) -> Unit,
    onCerrarSesion: () -> Unit,
    vm: CalidadViewModel = koinViewModel(),
) {
    val s by vm.uiState.collectAsState()
    var descartar by remember { mutableStateOf(false) }
    CalidadBackHandler(s.paso != PasoCalidad.INICIO) { vm.volver() }
    Scaffold(
        containerColor = Colores.bgBase,
        topBar = { BarraSuperior(s.paso.titulo,
            alVolver = if(s.paso != PasoCalidad.INICIO) ({ vm.volver() }) else null,
            accion = { IconButton(onClick = onCerrarSesion, enabled = !s.guardando) { Icon(Icons.AutoMirrored.Filled.Logout, "Cerrar sesión") } }) },
        bottomBar = {
            NavigationBar(containerColor = Colores.surface) {
                listOf(Triple(PasoCalidad.INICIO, "Inicio", Icons.Default.Home),
                    Triple(PasoCalidad.SELECCION, "Nuevo análisis", Icons.Default.AddCircle),
                    Triple(PasoCalidad.HISTORIAL, "Historial", Icons.Default.History)).forEach { (paso, nombre, icono) ->
                    NavigationBarItem(
                        selected = s.paso == paso || (paso == PasoCalidad.SELECCION && (s.paso.esFormulario || s.paso == PasoCalidad.GUARDADO)) ||
                            (paso == PasoCalidad.HISTORIAL && s.paso == PasoCalidad.DETALLE && s.volverDetalle == PasoCalidad.HISTORIAL),
                        enabled = !s.guardando,
                        onClick = { vm.navegar(paso) },
                        icon = { Icon(icono, nombre) }, label = { Text(nombre) },
                    )
                }
            }
        },
    ) { padding ->
        if(s.cargando) { Box(Modifier.padding(padding)) { IndicadorCarga() } }
        else key(s.paso) {
            LazyColumn(
                Modifier.fillMaxSize().padding(padding).imePadding(),
                contentPadding = PaddingValues(20.dp), verticalArrangement = Arrangement.spacedBy(16.dp),
            ) {
                s.error?.let { item { Banner(it, TipoBanner.ERROR) } }
                s.mensaje?.let { item { Banner(it, TipoBanner.INFO) } }
                when(s.paso) {
                    PasoCalidad.INICIO -> dashboard(s, vm)
                    PasoCalidad.SELECCION -> seleccion(s, vm)
                    PasoCalidad.FORMULARIO -> formulario(s, vm)
                    PasoCalidad.GUARDADO -> item { Guardado(vm) }
                    PasoCalidad.HISTORIAL -> historial(s, vm)
                    PasoCalidad.DETALLE -> detalle(s)
                }
                if(s.paso.esFormulario) item {
                    TextButton(onClick = { descartar = true }, enabled = !s.guardando) { Text("Descartar análisis") }
                }
            }
        }
    }
    if(descartar) AlertDialog(
        onDismissRequest = { descartar = false }, title = { Text("¿Descartar este análisis?") },
        text = { Text("Se perderán los datos que todavía no has guardado.") },
        confirmButton = { TextButton(onClick = { vm.descartar(); descartar = false }) { Text("Descartar") } },
        dismissButton = { TextButton(onClick = { descartar = false }) { Text("Continuar editando") } },
    )
    if(s.confirmarCambioProveedor) AlertDialog(
        onDismissRequest = vm::cancelarCambioProveedor, title = { Text("¿Cambiar de proveedor?") },
        text = { Text("Se perderán los resultados que ya escribiste o escaneaste para este proveedor.") },
        confirmButton = { TextButton(onClick = vm::confirmarCambioProveedor) { Text("Cambiar proveedor") } },
        dismissButton = { TextButton(onClick = vm::cancelarCambioProveedor) { Text("Continuar aquí") } },
    )
    if(s.escaneoPendiente != null) AlertDialog(
        onDismissRequest = vm::descartarEscaneo, title = { Text("¿Reemplazar los datos ya escritos?") },
        text = { Text("Ya hay resultados escritos en el formulario. El nuevo escaneo puede reemplazarlos.") },
        confirmButton = { TextButton(onClick = vm::confirmarEscaneo) { Text("Reemplazar") } },
        dismissButton = { TextButton(onClick = vm::descartarEscaneo) { Text("Conservar lo escrito") } },
    )
}

private fun LazyListScope.dashboard(s: CalidadUiState, vm: CalidadViewModel) {
    item {
        Text("Hola, ${s.tecnico}", style = MaterialTheme.typography.headlineSmall)
        Text(formatearFecha(s.ahora), color = Colores.textSecundario)
    }
    item { BotonPrimario(if(s.borrador.id.isBlank()) "Nuevo análisis" else "Continuar análisis", { vm.navegar(PasoCalidad.SELECCION) }, icono = Icons.Default.Add) }
    val fecha = Instant.fromEpochMilliseconds(s.ahora).toLocalDateTime(TimeZone.currentSystemDefault()).date
    val lunes = fecha.toEpochDays() - fecha.dayOfWeek.ordinal
    val semana = s.controles.filter {
        val dia = Instant.fromEpochMilliseconds(it.registradoEn).toLocalDateTime(TimeZone.currentSystemDefault()).date.toEpochDays()
        dia >= lunes && dia <= fecha.toEpochDays()
    }
    item {
        Text("Esta semana", style = MaterialTheme.typography.titleMedium)
        Row(Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            TarjetaEstadistica("Análisis", semana.size.toString(), Modifier.weight(1f))
            TarjetaEstadistica("Aprobados", semana.count { it.estado == EstadoControlCalidad.APROBADO }.toString(), Modifier.weight(1f))
            TarjetaEstadistica("Rechazados", semana.count { it.estado == EstadoControlCalidad.RECHAZADO }.toString(), Modifier.weight(1f))
        }
    }
    item {
        Text("Análisis recientes", style = MaterialTheme.typography.titleLarge)
        Text("Guardados en este dispositivo", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
    }
    if(s.controles.isEmpty()) item { EstadoVacio("Tu primer análisis comienza aquí",
        descripcion = "Elige la zona a la que te diriges y después el proveedor que vas a examinar.", icono = Icons.Default.Science) }
    items(s.controles.take(5), key = { it.id }) { c -> TarjetaVisita(c, s, { vm.detalle(c.id) }) }
}

private fun LazyListScope.seleccion(s: CalidadUiState, vm: CalidadViewModel) {
    item {
        Text(if(s.borrador.zonaId.isBlank()) "¿A qué zona te diriges?" else s.zona?.nombre.orEmpty(), style = MaterialTheme.typography.headlineSmall)
        Text("Selecciona la zona y toca el proveedor para continuar.", color = Colores.textSecundario)
    }
    if(s.borrador.zonaId.isBlank()) {
        if(s.zonas.isEmpty()) item { EstadoVacio("No hay zonas activas", descripcion = "Registra una zona desde la administración móvil.") }
        items(s.zonas, key = { it.id }) { z ->
            Tarjeta(onClick = { vm.zona(z.id) }) {
                Icon(Icons.Default.Groups, null, tint = Colores.brand)
                Text(z.nombre, style = MaterialTheme.typography.titleMedium, modifier = Modifier.padding(top = 8.dp))
                Text("${s.proveedores.count { it.zonaId == z.id && it.estado == EstadoProveedor.ACTIVO }} proveedores", color = Colores.textSecundario)
            }
        }
    } else {
        item {
            TextButton(onClick = vm::volver) { Text("Cambiar zona") }
            CampoTexto(s.busqueda, vm::buscar, "Buscar por nombre o código", iconoInicial = Icons.Default.Search)
        }
        if(s.proveedoresFiltrados.isEmpty()) item { EstadoVacio("No hay proveedores que coincidan", descripcion = "Prueba otra búsqueda o selecciona otra zona.") }
        items(s.proveedoresFiltrados, key = { it.id }) { p ->
            Tarjeta(onClick = { vm.proveedor(p.id) }) {
                Text(p.nombres, style = MaterialTheme.typography.titleMedium)
                Text("Dueño: ${p.dueno ?: "No registrado"}", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                Text(p.codigo, fontFamily = FontFamily.Monospace, color = Colores.brandText)
            }
        }
    }
}

private fun LazyListScope.formulario(s: CalidadUiState, vm: CalidadViewModel) {
    val b = s.borrador
    item {
        Tarjeta {
            Text(s.proveedor?.nombres.orEmpty(), style = MaterialTheme.typography.titleLarge)
            Text(s.zona?.nombre.orEmpty(), color = Colores.textSecundario)
            TextButton(onClick = vm::cambiarProveedor, enabled = !s.guardando) { Text("Cambiar proveedor") }
        }
    }
    item {
        Tarjeta {
            Icon(Icons.Default.DocumentScanner, null, tint = Colores.brand, modifier = Modifier.size(40.dp))
            Text("Coloca un solo comprobante dentro del encuadre, con buena iluminación.",
                style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario, modifier = Modifier.padding(vertical = 8.dp))
            EscanerComprobante(vm::escanear, vm::error)
        }
    }
    item { Text("Resultados del análisis", style = MaterialTheme.typography.titleLarge) }
    items(parametrosCalidad, key = { it.clave }) { p -> ParametroCampo(p, b, vm) }
    item { Text("Fecha y datos del equipo", style = MaterialTheme.typography.titleLarge) }
    item {
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            CampoTexto(b.fecha, { vm.campo("fecha", it) }, "Fecha (dd/mm/aaaa)", modifier = Modifier.weight(1f))
            CampoTexto(b.hora, { vm.campo("hora", it) }, "Hora (hh:mm)", modifier = Modifier.weight(1f))
        }
        CampoTexto(b.serial, { vm.campo("serial", it) }, "Número de serie del analizador", modifier = Modifier.padding(top = 8.dp))
        CampoTexto(b.modo, { vm.campo("modo", it) }, "Modo del analizador", modifier = Modifier.padding(top = 8.dp))
    }
    item { BotonPrimario("Guardar análisis", vm::guardar, cargando = s.guardando, icono = Icons.Default.Save) }
}

@Composable
private fun ParametroCampo(p: ParametroCalidad, b: BorradorVisita, vm: CalidadViewModel) {
    val valor = b.valores[p.clave].orEmpty()
    val vacio = valor.isBlank()
    val invalido = !vacio && !b.formatoValido(p.clave)
    val dudoso = !vacio && !invalido && p.clave in b.dudosos
    val correcto = !vacio && !invalido && b.correcto(p.clave)
    Tarjeta {
        Row(verticalAlignment = Alignment.CenterVertically) {
            Text(p.nombre, style = MaterialTheme.typography.titleMedium, modifier = Modifier.weight(1f))
            val (icono, descripcion, color) = when {
                vacio -> Triple(Icons.Default.RemoveCircleOutline, "Sin dato", Colores.textSecundario)
                invalido -> Triple(Icons.Default.ErrorOutline, "Formato inválido", Colores.peligro)
                dudoso -> Triple(Icons.Default.HelpOutline, "Escaneo dudoso", Colores.info)
                correcto -> Triple(Icons.Default.CheckCircle, "Dentro de referencia", Colores.exito)
                else -> Triple(Icons.Default.WarningAmber, "Fuera de referencia", Colores.peligro)
            }
            Icon(icono, descripcion, tint = color)
        }
        val unidad = if(p.clave == "congelacion") b.unidadCongelacion else p.unidad
        Numero("Valor ($unidad)", valor, invalido || (!vacio && !correcto && !dudoso)) { vm.campo(p.clave, it) }
        if(p.clave == "congelacion") Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf("°C", "°H").forEach { u -> FilterChip(selected = b.unidadCongelacion == u,
                onClick = { vm.campo("unidad", u) }, label = { Text(u) }) }
        }
        Text(
            when {
                invalido -> if(permiteNegativo(p.clave)) "Usa solo números, con coma o punto decimal." else "Usa solo números positivos, con coma o punto decimal."
                dudoso -> "El comprobante trae más de un número en esta línea; verifica el valor."
                p.clave == "congelacion" && b.unidadCongelacion == "°H" -> "Referencia en °H pendiente de configurar; requiere revisión."
                else -> "Referencia: ${p.referencia}"
            },
            style = MaterialTheme.typography.bodySmall,
            color = if(invalido) Colores.peligro else if(dudoso) Colores.info else Colores.textSecundario,
        )
        if(p.clave == "agua" && (b.numero("agua") ?: 0.0) > 0) Text("CRÍTICO · Posible agua añadida", color = Colores.peligro)
    }
}

@Composable
private fun Guardado(vm: CalidadViewModel) {
    Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
        Banner("Análisis guardado correctamente.", TipoBanner.EXITO)
        BotonPrimario("Registrar otro análisis", { vm.navegar(PasoCalidad.SELECCION) }, icono = Icons.Default.Add)
        BotonSecundario("Ver historial", { vm.navegar(PasoCalidad.HISTORIAL) }, icono = Icons.Default.History)
    }
}

private fun LazyListScope.historial(s: CalidadUiState, vm: CalidadViewModel) {
    item { Text("Todos mis análisis", style = MaterialTheme.typography.headlineSmall) }
    item {
        LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            item { FilterChip(selected = s.filtro == null, onClick = { vm.filtrar(null) }, label = { Text("Todos") }) }
            items(listOf(EstadoControlCalidad.APROBADO, EstadoControlCalidad.OBSERVADO, EstadoControlCalidad.RECHAZADO)) { e ->
                FilterChip(selected = s.filtro == e, onClick = { vm.filtrar(e) }, label = { Text(if(e == EstadoControlCalidad.OBSERVADO) "Con observaciones" else e.etiqueta()) })
            }
        }
    }
    if(s.historial.isEmpty()) item { EstadoVacio("No hay análisis con este filtro") }
    items(s.historial, key = { it.id }) { c -> TarjetaVisita(c, s) { vm.detalle(c.id) } }
}

@Composable
private fun TarjetaVisita(c: ControlCalidad, s: CalidadUiState, click: () -> Unit) {
    Tarjeta(onClick = click) {
        Text(c.visita.proveedorNombre.ifBlank { s.proveedores.firstOrNull { it.id == c.proveedorId }?.nombres ?: "Proveedor no disponible" },
            style = MaterialTheme.typography.titleMedium)
        Text(c.visita.zonaNombre.ifBlank { s.zonas.firstOrNull { it.id == c.visita.zonaId }?.nombre.orEmpty() }, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
        Text(formatearFechaHora(c.registradoEn), style = MaterialTheme.typography.bodySmall)
        Text(c.visita.tecnicoNombre.ifBlank { s.tecnico }, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
        ChipEstado(c.estado.etiqueta(), c.estado.color(), Modifier.padding(top = 8.dp))
    }
}

private fun LazyListScope.detalle(s: CalidadUiState) {
    val c = s.detalle
    if(c == null) { item { EstadoVacio("El análisis no está disponible") }; return }
    val v = c.visita
    item {
        Text(v.proveedorNombre.ifBlank { s.proveedores.firstOrNull { it.id == c.proveedorId }?.nombres.orEmpty() }, style = MaterialTheme.typography.headlineSmall)
        Text(v.zonaNombre.ifBlank { s.zonas.firstOrNull { it.id == v.zonaId }?.nombre.orEmpty() }, color = Colores.textSecundario)
        Text(formatearFechaHora(c.registradoEn), color = Colores.textSecundario)
        ChipEstado(c.estado.etiqueta(), c.estado.color(), Modifier.padding(top = 8.dp))
    }
    item { Tarjeta {
        Text("Trazabilidad", style = MaterialTheme.typography.titleMedium)
        Dato("Proveedor", v.proveedorNombre.ifBlank { "No disponible" })
        Dato("Zona", v.zonaNombre.ifBlank { "No disponible" })
        Dato("Técnico", v.tecnicoNombre.ifBlank { "No disponible" })
        Dato("Analizador / modo", "${c.serialAnalizador.orEmpty()} / ${c.modoAnalizador.orEmpty()}")
        Dato("Origen", if(c.origenCaptura == OrigenCaptura.ESCANER) "Comprobante escaneado" else "Ingreso manual")
    } }
    item { Text("Parámetros del analizador", style = MaterialTheme.typography.titleLarge) }
    items(parametrosCalidad, key = { it.clave }) { p ->
        val valor = c.valores()[p.clave]
        val conocido = v.referencias.containsKey(p.clave)
        val ok = if(conocido) p.clave !in v.parametrosAlertados && valor != null else p.correcto(valor)
        Tarjeta {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(p.nombre, modifier = Modifier.weight(1f))
                Text(valor?.toString() ?: "Sin dato", fontFamily = FontFamily.Monospace,
                    color = if(valor == null) Colores.textSecundario else if(ok) Colores.exito else Colores.peligro)
            }
            Text(v.referencias[p.clave] ?: "Referencia actual: ${p.referencia}", style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
        }
    }
    val esRegistroAntiguo = v.finca.isNotBlank() || v.personaAtiende.isNotBlank() || c.codigoMuestra.startsWith("MUE-") ||
        v.accionTomada.isNotBlank() || v.tipoLeche.isNotBlank()
    if(esRegistroAntiguo) item { Tarjeta {
        Text("Datos de la visita (registro anterior)", style = MaterialTheme.typography.titleMedium)
        Dato("Finca", v.finca); Dato("Persona que atiende", v.personaAtiende)
        Dato("Muestra", c.codigoMuestra); Dato("Tipo de leche", v.tipoLeche)
        Dato("Tanque / lote", c.loteRecipiente.orEmpty()); Dato("Volumen", c.volumenL?.let { "$it L" }.orEmpty())
        Dato("Apariencia", c.apariencia.orEmpty()); Dato("Notas", c.observaciones.orEmpty())
        Dato("Acción tomada", v.accionTomada); Dato("Motivo", v.motivo)
    } }
    c.textoComprobante?.let { texto -> item { Tarjeta {
        Text("Texto original del comprobante", style = MaterialTheme.typography.titleMedium)
        Text(texto, fontFamily = FontFamily.Monospace, style = MaterialTheme.typography.bodySmall)
    } } }
}

internal fun EstadoControlCalidad.etiqueta() = when(this) {
    EstadoControlCalidad.APROBADO -> "Aprobado"
    EstadoControlCalidad.OBSERVADO -> "Aprobado con observaciones"
    EstadoControlCalidad.RECHAZADO -> "Rechazado"
    EstadoControlCalidad.REPETIR -> "Requiere repetir análisis"
}
@Composable internal fun EstadoControlCalidad.color() = when(this) {
    EstadoControlCalidad.APROBADO -> Colores.exito
    EstadoControlCalidad.OBSERVADO -> Colores.advertencia
    EstadoControlCalidad.RECHAZADO -> Colores.peligro
    EstadoControlCalidad.REPETIR -> Colores.info
}
@Composable private fun Dato(nombre: String, valor: String) {
    Text(nombre, style = MaterialTheme.typography.labelMedium, color = Colores.textSecundario, modifier = Modifier.padding(top = 10.dp))
    Text(valor.ifBlank { "No registrado" })
}
@Composable private fun Numero(nombre: String, valor: String, error: Boolean = false, cambio: (String) -> Unit) {
    OutlinedTextField(value = valor, onValueChange = cambio, label = { Text(nombre) }, singleLine = true,
        isError = error, textStyle = MaterialTheme.typography.bodyLarge.copy(fontFamily = FontFamily.Monospace),
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Text), modifier = Modifier.fillMaxWidth())
}
@Composable expect fun CalidadBackHandler(enabled: Boolean, onBack: () -> Unit)
