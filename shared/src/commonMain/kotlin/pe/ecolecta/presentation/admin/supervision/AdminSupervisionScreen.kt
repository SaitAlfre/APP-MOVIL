package pe.ecolecta.presentation.admin.supervision

import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminTopBar
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontFamily
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.calidad.parametrosCalidad
import pe.ecolecta.domain.calidad.valores
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.design.*

@Composable
fun AdminCalidadScreen(proveedorId: String?, alVolver: () -> Unit, vm: AdminSupervisionViewModel = koinViewModel()) {
    val datos by vm.state.collectAsState()
    var busqueda by rememberSaveable { mutableStateOf("") }
    var fecha by rememberSaveable { mutableStateOf("") }
    var zona by rememberSaveable { mutableStateOf<String?>(null) }
    var estado by rememberSaveable { mutableStateOf<String?>(null) }
    var seleccionado by rememberSaveable { mutableStateOf<String?>(null) }
    val detalle = datos.controles.find { it.id == seleccionado }
    pe.ecolecta.presentation.calidad.CalidadBackHandler(true) {
        if (seleccionado != null) seleccionado = null else alVolver()
    }
    androidx.compose.foundation.layout.Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
    AdminTopBar(
        if (seleccionado == null) "Control de calidad" else "Detalle del análisis",
        "Solo consulta · Datos de este dispositivo",
        alVolver = { if (seleccionado != null) seleccionado = null else alVolver() },
    )
    LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(Espaciado.m), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
        when {
            datos.cargando -> item { IndicadorCarga() }
            datos.error != null -> item { Text(datos.error.orEmpty()); TextButton(onClick = vm::cargar) { Text("Reintentar") } }
            seleccionado != null -> {
                if (detalle == null) item { Text("El análisis ya no está disponible.") }
                else item { DetalleCalidadAdmin(detalle, datos) }
            }
            else -> {
                if (proveedorId != null) item { Text("Proveedor: ${datos.proveedores.find { it.id == proveedorId }?.nombres ?: "No disponible"}", style = MaterialTheme.typography.titleMedium) }
                item {
                    pe.ecolecta.presentation.admin.design.AdminTexto(
                        "Administración consulta los análisis que registra el técnico; no los recalifica. " +
                            "El resultado y las alertas son los que se guardaron al registrar el análisis.",
                        12, AdminColor.gris,
                    )
                }
                item { CampoTexto(busqueda, { busqueda = it }, "Proveedor, código o técnico") }
                item { CampoTexto(fecha, { fecha = it }, "Fecha (dd/mm/aaaa)", ayuda = "Déjala vacía para ver todas las fechas") }
                item {
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                        item { ChipSeleccionable("Todas las zonas", zona == null) { zona = null } }
                        items(datos.zonas, key = { it.id }) { z -> ChipSeleccionable(z.nombre, zona == z.id) { zona = z.id } }
                    }
                }
                item {
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                        item { ChipSeleccionable("Todos", estado == null) { estado = null } }
                        items(EstadoControlCalidad.entries) { e -> ChipSeleccionable(e.etiquetaAdmin(), estado == e.name) { estado = e.name } }
                    }
                }
                val controles = filtrarControlesAdmin(datos, proveedorId, zona, estado, busqueda, fecha)
                item { Text("${controles.size} análisis encontrados", style = MaterialTheme.typography.labelLarge) }
                if (controles.isEmpty()) item { Text("No hay análisis para estos filtros. Los ejemplos no se incluyen.") }
                items(controles, key = { it.id }) { c ->
                    Tarjeta(onClick = { seleccionado = c.id }) {
                        Text(datos.nombre(c), style = MaterialTheme.typography.titleMedium)
                        Text("${c.codigoMuestra} · ${formatearFechaHora(c.registradoEn)}")
                        Text(datos.tecnico(c), color = Colores.textSecundario)
                        EstadoAnalisis(c)
                    }
                }
            }
        }
    }
    }
}

@Composable
private fun EstadoAnalisis(c: ControlCalidad) {
    Text(c.estado.etiquetaAdmin(), color = when (c.estado) {
        EstadoControlCalidad.APROBADO -> Colores.brand
        EstadoControlCalidad.RECHAZADO -> Colores.peligro
        else -> Colores.advertencia
    }, style = MaterialTheme.typography.labelLarge)
}

@Composable
private fun DetalleCalidadAdmin(c: ControlCalidad, datos: AdminSupervisionState) {
    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
        Tarjeta {
            Text(datos.nombre(c), style = MaterialTheme.typography.titleLarge)
            Text("Muestra: ${c.codigoMuestra}")
            Text("Registro: ${c.id}")
            Text("Fecha: ${formatearFechaHora(c.registradoEn)}")
            Text("Zona: ${c.visita.zonaNombre.ifBlank { datos.zonas.find { it.id == datos.zonaId(c) }?.nombre ?: "No registrada" }}")
            EstadoAnalisis(c)
        }
        Text("Valores registrados", style = MaterialTheme.typography.titleMedium)
        Text("Se conservan las referencias del análisis original; no se recalifica con límites nuevos.", color = Colores.textSecundario)
        val valores = c.valores()
        parametrosCalidad.forEach { p ->
            Tarjeta {
                val alertado = p.clave in c.visita.parametrosAlertados
                Text(p.nombre)
                val unidad = if (p.clave == "congelacion") c.visita.unidadCongelacion else p.unidad
                Text("${valores[p.clave]?.toString() ?: "Sin dato"} $unidad", fontFamily = FontFamily.Monospace,
                    color = if (alertado) Colores.peligro else Colores.textPrimary)
                Text(c.visita.referencias[p.clave]?.let { "Referencia: $it" } ?: "Referencia no registrada", style = MaterialTheme.typography.bodySmall)
                if (alertado) Text("⚠ Fuera de referencia", color = Colores.peligro)
            }
        }
        Tarjeta {
            Text("Alertas y observaciones", style = MaterialTheme.typography.titleMedium)
            if (c.alertas.isEmpty()) Text("Sin alertas registradas") else c.alertas.forEach { Text(it, color = Colores.peligro) }
            c.observaciones?.takeIf { it.isNotBlank() }?.let { Text(it) }
            Text("Acción tomada: ${c.visita.accionTomada.ifBlank { "No registrada" }}")
            if (c.visita.motivo.isNotBlank()) Text("Motivo: ${c.visita.motivo}")
        }
        Tarjeta {
            Text("Trazabilidad", style = MaterialTheme.typography.titleMedium)
            Text("Registrado por: ${datos.tecnico(c)}")
            Text("Creado: ${formatearFechaHora(c.visita.creadaEn ?: c.registradoEn)}")
            Text("Confirmado por: ${c.visita.confirmadoNombre.ifBlank { c.visita.confirmadoPor.ifBlank { "No registrado" } }}")
            Text("Confirmación: ${c.visita.confirmadaEn?.let { formatearFechaHora(it) } ?: "No registrada"}")
            Text("Captura: ${if (c.origenCaptura == OrigenCaptura.ESCANER) "Escáner" else "Manual"}")
            Text("Analizador: ${c.serialAnalizador ?: "Sin serie"} · Modo ${c.modoAnalizador ?: "no registrado"}")
        }
        Tarjeta {
            Text("Texto del comprobante", style = MaterialTheme.typography.titleMedium)
            Text(c.textoComprobante?.takeIf { it.isNotBlank() } ?: "No hay texto escaneado guardado.", fontFamily = FontFamily.Monospace)
        }
    }
}

@Composable
fun AdminProveedorDetalleScreen(id: String, alVolver: () -> Unit, alEditar: () -> Unit, alCalidad: () -> Unit,
    alEntrega: (String) -> Unit, vm: AdminSupervisionViewModel = koinViewModel()) {
    val datos by vm.state.collectAsState()
    val p = datos.proveedores.find { it.id == id }
    pe.ecolecta.presentation.calidad.CalidadBackHandler(true, alVolver)
    androidx.compose.foundation.layout.Column(Modifier.fillMaxSize().background(AdminColor.crema)) {
    AdminTopBar(p?.nombres ?: "Proveedor", p?.codigo, alVolver = alVolver)
    LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(Espaciado.m), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
        when {
            datos.cargando -> item { IndicadorCarga() }
            datos.error != null -> item { Text(datos.error.orEmpty()); TextButton(onClick = vm::cargar) { Text("Reintentar") } }
            p == null -> item { Text("Proveedor no disponible") }
            else -> {
                item { Tarjeta {
                    Text(p.nombres, style = MaterialTheme.typography.headlineSmall)
                    Text("Código: ${p.codigo}")
                    Text("Responsable: ${p.dueno ?: "No registrado"}")
                    Text("DNI: ${p.dni}")
                    Text("Zona: ${datos.zonas.find { it.id == p.zonaId }?.nombre ?: "No disponible"}")
                    Text("Teléfono: ${p.telefono ?: "No registrado"}")
                    Text("Dirección: ${p.direccion ?: "No registrada"}")
                    Text("Estado: ${p.estado.name.lowercase().replaceFirstChar { it.uppercase() }}")
                    Text("${p.tachos} tachos · ${formatearLitros(p.capacidadTotalL)} L de capacidad")
                    TextButton(onClick = alEditar) { Text("Editar datos del proveedor") }
                } }
                val controles = datos.controles.filter { it.proveedorId == id }
                item { Tarjeta(onClick = alCalidad) {
                    Text("Calidad · ${controles.size} análisis", style = MaterialTheme.typography.titleMedium)
                    controles.firstOrNull()?.let { EstadoAnalisis(it); Text("Último: ${formatearFechaHora(it.registradoEn)}") }
                    Text("Ver historial de calidad →", color = Colores.brand)
                } }
                val entregas = datos.entregas.filter { it.proveedorId == id }
                item { Text("Entregas · Datos de este dispositivo", style = MaterialTheme.typography.titleMedium) }
                if (entregas.isEmpty()) item { Text("Todavía no hay entregas registradas.") }
                items(entregas, key = { it.id }) { e -> Tarjeta(onClick = { alEntrega(e.id) }) {
                    Text("${formatearLitros(e.litros)} L · ${formatearFechaHora(e.registradoEn)}")
                    if (e.anulada) Text("Anulada", color = Colores.peligro)
                    Text("Ver entrega →", color = Colores.brand)
                } }
            }
        }
    }
    }
}
