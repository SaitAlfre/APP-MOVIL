package pe.ecolecta.presentation.acopiador.lista

import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.RadioButton
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import kotlinx.datetime.LocalDate
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.acopio.CicloAcopio
import pe.ecolecta.domain.acopio.DiaAcopio
import pe.ecolecta.domain.acopio.EstadoRecojo
import pe.ecolecta.domain.acopio.EstadoSincronizacion
import pe.ecolecta.domain.acopio.FilaAcopio
import pe.ecolecta.domain.acopio.MotivoSinRecojo
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.CampoBusqueda
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.PildoraPendientes
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearHoraAcopio
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun ListaProveedoresScreen(
    alRegistrarEntrega: (String) -> Unit,
    pendientesSync: Int = 0,
    viewModel: ListaProveedoresViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu lista…")
        return
    }

    val ciclo = estado.ciclo
    val hoy = estado.hoy
    if (ciclo == null || hoy == null) {
        EstadoVacio(
            titulo = "No tienes una jornada abierta",
            descripcion = "Abre tu jornada para ver a los proveedores asignados a tu zona.",
        )
        return
    }

    var paraSinRecojo by remember { mutableStateOf<FilaAcopio?>(null) }

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Lista de acopio",
            subtitulo = ciclo.resumenCorto,
            accion = { PildoraPendientes(pendientesSync) },
        )
        Column(
            Modifier.padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                ChipSeleccionable("Hoy", estado.modo == ModoLista.HOY) { viewModel.cambiarModo(ModoLista.HOY) }
                ChipSeleccionable("Ciclo (6 días)", estado.modo == ModoLista.CICLO) { viewModel.cambiarModo(ModoLista.CICLO) }
            }
            if (!estado.remotoConfigurado) {
                Banner(
                    "Este celular no tiene sincronización configurada: los registros quedan guardados solo aquí " +
                        "y el proveedor no los verá en su celular.",
                    TipoBanner.INFO,
                )
            }
            if (!estado.jornadaAbierta) {
                Banner("Tu jornada está cerrada. Para corregir, reábrela desde Jornada (queda auditado).", TipoBanner.ADVERTENCIA)
            }
            estado.error?.let { Banner(it, TipoBanner.ERROR) }
        }

        when (estado.modo) {
            ModoLista.HOY -> VistaHoy(estado, ciclo, hoy, viewModel, alRegistrarEntrega) { paraSinRecojo = it }
            ModoLista.CICLO -> VistaCiclo(estado, ciclo, viewModel)
        }
    }

    paraSinRecojo?.let { fila ->
        DialogoSinRecojo(
            fila = fila,
            ciclo = ciclo,
            onConfirmar = { motivo, detalle ->
                viewModel.marcarSinRecojo(fila.proveedor.id, motivo, detalle)
                paraSinRecojo = null
            },
            onCancelar = { paraSinRecojo = null },
        )
    }

    estado.detalle?.let { fila ->
        DialogoDetalle(
            fila = fila,
            hoy = hoy,
            ciclo = ciclo,
            puedeRegistrar = estado.jornadaAbierta,
            onRegistrarOtra = {
                viewModel.cerrarDetalle()
                alRegistrarEntrega(fila.proveedor.id)
            },
            onCerrar = viewModel::cerrarDetalle,
        )
    }
}

// ---------------------------------------------------------------------------------------------
// Hoy: la lista de trabajo del día
// ---------------------------------------------------------------------------------------------

@Composable
private fun VistaHoy(
    estado: ListaProveedoresUiState,
    ciclo: CicloAcopio,
    hoy: LocalDate,
    viewModel: ListaProveedoresViewModel,
    alRegistrarEntrega: (String) -> Unit,
    alMarcarSinRecojo: (FilaAcopio) -> Unit,
) {
    Column(Modifier.fillMaxSize()) {
        Column(
            Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs),
            verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            TarjetaCiclo(ciclo)
            TarjetaAvance(estado)
            CampoBusqueda(estado.busqueda, viewModel::buscar, "Buscar proveedor o código")
            Row(
                Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                horizontalArrangement = Arrangement.spacedBy(Espaciado.xs),
            ) {
                FiltroLista.entries.forEach { filtro ->
                    ChipSeleccionable(filtro.etiqueta, estado.filtro == filtro) { viewModel.filtrar(filtro) }
                }
            }
        }

        if (estado.filas.isEmpty()) {
            EstadoVacio(
                titulo = "Sin proveedores asignados",
                descripcion = "No hay proveedores activos asignados a la zona ${estado.zonaNombre}.",
            )
        } else if (estado.visibles.isEmpty()) {
            EstadoVacio(titulo = "Ningún proveedor coincide", descripcion = "Prueba con otro filtro o borra la búsqueda.")
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.xs),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
                contentPadding = PaddingValues(bottom = Espaciado.xl),
            ) {
                items(estado.visibles, key = { it.proveedor.id }) { fila ->
                    FilaDeHoy(
                        fila = fila,
                        dia = fila.dia(hoy),
                        jornadaAbierta = estado.jornadaAbierta,
                        procesando = estado.procesando,
                        onRegistrar = { alRegistrarEntrega(fila.proveedor.id) },
                        onSinRecojo = { alMarcarSinRecojo(fila) },
                        onDeshacer = { marcaId -> viewModel.deshacerSinRecojo(marcaId) },
                        onDetalle = { viewModel.abrirDetalle(fila.proveedor.id) },
                    )
                }
            }
        }
    }
}

@Composable
private fun TarjetaAvance(estado: ListaProveedoresUiState) {
    val avance = estado.avance
    Tarjeta {
        Text("AVANCE DE HOY · ZONA ${estado.zonaNombre.uppercase()}", color = Colores.textSecundario, style = MaterialTheme.typography.labelMedium)
        Spacer(Modifier.height(Espaciado.xxs))
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Cifra("${avance.asignados}", "Asignados", Colores.textPrimary)
            Cifra("${avance.registrados}", "Registrados", Colores.exito)
            Cifra("${avance.pendientes}", "Pendientes", Colores.info)
            Cifra("${avance.sinRecojo}", "Sin recojo", Colores.advertencia)
        }
    }
}

@Composable
private fun Cifra(valor: String, etiqueta: String, color: Color) {
    Column(horizontalAlignment = Alignment.CenterHorizontally) {
        Text(valor, color = color, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Bold)
        Text(etiqueta, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
    }
}

@Composable
private fun FilaDeHoy(
    fila: FilaAcopio,
    dia: DiaAcopio?,
    jornadaAbierta: Boolean,
    procesando: Boolean,
    onRegistrar: () -> Unit,
    onSinRecojo: () -> Unit,
    onDeshacer: (String) -> Unit,
    onDetalle: () -> Unit,
) {
    val estadoRecojo = dia?.estado ?: EstadoRecojo.PENDIENTE
    val detalle = when (estadoRecojo) {
        EstadoRecojo.PENDIENTE -> "Pendiente · todavía no registrado"
        EstadoRecojo.REGISTRADO -> dia!!.let { d ->
            val veces = if (d.recojos.size > 1) " · ${d.recojos.size} entregas" else ""
            "Último registro ${formatearHoraAcopio(d.horaUltima!!)}$veces"
        }
        EstadoRecojo.SIN_RECOJO -> "Sin recojo · ${dia!!.sinRecojo!!.textoMotivo}"
    }

    Tarjeta(onClick = onDetalle) {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(fila.proveedor.codigo, color = Colores.textSecundario, style = MaterialTheme.typography.labelMedium)
                // Nombre completo tal como está guardado (no se separa en apellido/nombre).
                Text(fila.proveedor.nombres, color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
                fila.proveedor.dueno?.let { Text(it, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall) }
                Text(detalle, color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium, maxLines = 2, overflow = TextOverflow.Ellipsis)
            }
            Spacer(Modifier.width(Espaciado.s))
            Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(Espaciado.xxs)) {
                when (estadoRecojo) {
                    EstadoRecojo.PENDIENTE -> ChipEstado("Pendiente", Colores.info, mostrarPunto = false)
                    EstadoRecojo.REGISTRADO -> {
                        Text(formatearLitros(dia!!.totalLitros), color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
                        ChipEstado("Registrado", Colores.exito, mostrarPunto = false)
                    }
                    EstadoRecojo.SIN_RECOJO -> ChipEstado("Sin recojo", Colores.advertencia, mostrarPunto = false)
                }
                dia?.sincronizacion?.let { ChipSincronizacion(it) }
            }
        }
        if (jornadaAbierta) {
            Spacer(Modifier.height(Espaciado.xs))
            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                when (estadoRecojo) {
                    EstadoRecojo.PENDIENTE -> {
                        BotonPildora("Registrar", Colores.brand, Colores.onBrand, onRegistrar)
                        BotonPildora("Sin recojo", Colores.advertencia, Colores.onSecundario, if (procesando) null else onSinRecojo)
                    }
                    EstadoRecojo.REGISTRADO -> BotonPildora("Registrar otra entrega", Colores.brandContainer, Colores.onBrandContainer, onRegistrar)
                    EstadoRecojo.SIN_RECOJO -> {
                        val marcaId = dia!!.sinRecojo!!.id
                        BotonPildora("Deshacer sin recojo", Colores.advertencia, Colores.onSecundario, if (procesando) null else ({ onDeshacer(marcaId) }))
                    }
                }
            }
        }
    }
}

@Composable
private fun ChipSincronizacion(estado: EstadoSincronizacion) {
    val color = when (estado) {
        EstadoSincronizacion.SINCRONIZADO -> Colores.exito
        EstadoSincronizacion.PENDIENTE -> Colores.info
        EstadoSincronizacion.EN_ESTE_CELULAR -> Colores.textSecundario
        EstadoSincronizacion.ERROR -> Colores.peligro
    }
    ChipEstado(estado.etiqueta, color, mostrarPunto = true)
}

@Composable
private fun BotonPildora(texto: String, contenedor: Color, contenido: Color, onClick: (() -> Unit)?) {
    Surface(
        shape = MaterialTheme.shapes.small,
        color = if (onClick == null) contenedor.copy(alpha = 0.5f) else contenedor,
        contentColor = contenido,
        modifier = if (onClick != null) Modifier.clickable(onClick = onClick) else Modifier,
    ) {
        Text(
            texto,
            style = MaterialTheme.typography.labelLarge,
            fontWeight = FontWeight.Bold,
            modifier = Modifier.padding(horizontal = Espaciado.m, vertical = Espaciado.xs),
        )
    }
}

/** "Sin recojo" exige elegir un motivo; el detalle libre es opcional. Se puede deshacer mientras la jornada siga abierta. */
@Composable
private fun DialogoSinRecojo(
    fila: FilaAcopio,
    ciclo: CicloAcopio,
    onConfirmar: (MotivoSinRecojo, String?) -> Unit,
    onCancelar: () -> Unit,
) {
    var motivo by remember { mutableStateOf<MotivoSinRecojo?>(null) }
    var detalle by remember { mutableStateOf("") }

    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text("Marcar sin recojo", style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                Text("${fila.proveedor.codigo} · ${fila.proveedor.nombres}", color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
                Text("Día ${ciclo.dia} de ${ciclo.totalDias}. Elige el motivo:", color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
                MotivoSinRecojo.entries.forEach { opcion ->
                    Row(
                        Modifier.fillMaxWidth().selectable(selected = motivo == opcion, onClick = { motivo = opcion }),
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        RadioButton(selected = motivo == opcion, onClick = { motivo = opcion })
                        Text(opcion.etiqueta, style = MaterialTheme.typography.bodyLarge)
                    }
                }
                CampoTexto(valor = detalle, onValorCambia = { detalle = it }, etiqueta = "Detalle (opcional)")
                Text("Puedes deshacerlo mientras tu jornada siga abierta.", color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
            }
        },
        confirmButton = {
            TextButton(onClick = { motivo?.let { onConfirmar(it, detalle) } }, enabled = motivo != null) {
                Text("Marcar sin recojo", color = if (motivo != null) Colores.peligro else Colores.textSecundario)
            }
        },
        dismissButton = { TextButton(onClick = onCancelar) { Text("Cancelar") } },
    )
}

/** Detalle del proveedor en el ciclo: cada entrega por separado, su hora y el total del día. Solo lectura. */
@Composable
private fun DialogoDetalle(
    fila: FilaAcopio,
    hoy: LocalDate,
    ciclo: CicloAcopio,
    puedeRegistrar: Boolean,
    onRegistrarOtra: () -> Unit,
    onCerrar: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = onCerrar,
        shape = MaterialTheme.shapes.large,
        title = { Text(fila.proveedor.nombres, style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                Text("${fila.proveedor.codigo} · ${ciclo.resumenPago}", color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
                fila.dias.forEachIndexed { indice, dia ->
                    Column {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Text(
                                "Día ${indice + 1} · ${fechaCorta(dia.fecha)}${if (dia.fecha == hoy) " (hoy)" else ""}",
                                style = MaterialTheme.typography.labelLarge,
                                color = Colores.textPrimary,
                            )
                            Text(
                                when (dia.estado) {
                                    EstadoRecojo.REGISTRADO -> "Total ${formatearLitros(dia.totalLitros)}"
                                    EstadoRecojo.SIN_RECOJO -> "Sin recojo"
                                    EstadoRecojo.PENDIENTE -> "Pendiente"
                                },
                                style = MaterialTheme.typography.labelLarge,
                                color = Colores.textSecundario,
                            )
                        }
                        dia.recojos.forEach { r ->
                            Text(
                                "• ${formatearHoraAcopio(r.registradoEn)} · ${formatearLitros(r.litros)} · ${r.tachos} tachos · ${r.sincronizacion.etiqueta}",
                                style = MaterialTheme.typography.bodyMedium,
                            )
                        }
                        dia.sinRecojo?.let { m ->
                            if (dia.recojos.isEmpty()) {
                                Text(
                                    "• ${formatearHoraAcopio(m.registradaEn)} · ${m.textoMotivo} · ${m.sincronizacion.etiqueta}",
                                    style = MaterialTheme.typography.bodyMedium,
                                )
                            }
                        }
                    }
                }
                Text("Total del ciclo: ${formatearLitros(fila.totalCicloLitros)}", style = MaterialTheme.typography.titleMedium)
            }
        },
        confirmButton = {
            if (puedeRegistrar) TextButton(onClick = onRegistrarOtra) { Text("Registrar otra entrega") }
        },
        dismissButton = { TextButton(onClick = onCerrar) { Text("Cerrar") } },
    )
}

// ---------------------------------------------------------------------------------------------
// Ciclo: la hoja Zona | Proveedor | Día 1 … Día 6
// ---------------------------------------------------------------------------------------------

private val ANCHO_ZONA = 84.dp
private val ANCHO_PROVEEDOR = 132.dp
private val ANCHO_DIA = 64.dp
private val ALTO_FILA = 52.dp

@Composable
private fun VistaCiclo(estado: ListaProveedoresUiState, ciclo: CicloAcopio, viewModel: ListaProveedoresViewModel) {
    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(horizontal = Espaciado.l, vertical = Espaciado.xs),
        verticalArrangement = Arrangement.spacedBy(Espaciado.s),
    ) {
        TarjetaCiclo(ciclo)
        Text(
            "Litros por día. \"—\" = pendiente (aún sin registro), \"SR\" = sin recojo, \"•\" = falta sincronizar. " +
                "Toca una fila para ver cada entrega.",
            color = Colores.textSecundario,
            style = MaterialTheme.typography.bodySmall,
        )
        if (estado.filas.isEmpty()) {
            EstadoVacio(titulo = "Sin proveedores asignados", descripcion = "No hay proveedores activos en esta zona.")
        } else {
            TablaCiclo(estado.filas, ciclo, estado.hoy, viewModel::abrirDetalle)
        }
        Text("El día ${ciclo.totalDias} incluye recolección y pago.", color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
        Tarjeta {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Text("Total del ciclo registrado", color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
                Text(formatearLitros(estado.totalCicloL), color = Colores.textPrimary, style = MaterialTheme.typography.headlineSmall)
            }
        }
        Spacer(Modifier.height(Espaciado.l))
    }
}

/** Zona y proveedor quedan fijos; solo se desplazan los 6 días (en 390 px no caben todas las columnas). */
@Composable
private fun TablaCiclo(filas: List<FilaAcopio>, ciclo: CicloAcopio, hoy: LocalDate?, onFila: (String) -> Unit) {
    Tarjeta(padding = 0.dp) {
        Row(Modifier.fillMaxWidth()) {
            Column(Modifier.width(ANCHO_ZONA + ANCHO_PROVEEDOR)) {
                Row {
                    CeldaEncabezado("ZONA", Modifier.width(ANCHO_ZONA), TextAlign.Start)
                    CeldaEncabezado("PROVEEDOR", Modifier.width(ANCHO_PROVEEDOR), TextAlign.Start)
                }
                filas.forEach { fila ->
                    DivisorSutil()
                    Row(Modifier.height(ALTO_FILA).clickable { onFila(fila.proveedor.id) }, verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            ciclo.zonaNombre,
                            color = Colores.textSecundario,
                            style = MaterialTheme.typography.bodySmall,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis,
                            modifier = Modifier.width(ANCHO_ZONA).padding(horizontal = Espaciado.s),
                        )
                        Text(
                            fila.proveedor.nombres,
                            color = Colores.textPrimary,
                            style = MaterialTheme.typography.bodyMedium,
                            maxLines = 2,
                            overflow = TextOverflow.Ellipsis,
                            modifier = Modifier.width(ANCHO_PROVEEDOR).padding(end = Espaciado.s),
                        )
                    }
                }
            }
            Column(Modifier.horizontalScroll(rememberScrollState())) {
                Row {
                    ciclo.dias.forEachIndexed { i, dia ->
                        CeldaEncabezado(
                            "Día ${i + 1}\n${fechaCorta(dia)}",
                            Modifier.width(ANCHO_DIA),
                            destacada = dia == hoy,
                        )
                    }
                }
                filas.forEach { fila ->
                    DivisorSutil()
                    Row(Modifier.clickable { onFila(fila.proveedor.id) }) {
                        fila.dias.forEach { dia -> CeldaDia(dia) }
                    }
                }
            }
        }
    }
}

@Composable
private fun CeldaEncabezado(texto: String, modifier: Modifier = Modifier, alineacion: TextAlign = TextAlign.Center, destacada: Boolean = false) {
    Box(
        modifier.height(ALTO_FILA).padding(horizontal = Espaciado.xs),
        contentAlignment = if (alineacion == TextAlign.Start) Alignment.CenterStart else Alignment.Center,
    ) {
        Text(
            texto,
            color = if (destacada) Colores.brandText else Colores.textSecundario,
            style = MaterialTheme.typography.labelMedium,
            fontWeight = if (destacada) FontWeight.Bold else null,
            textAlign = alineacion,
        )
    }
}

@Composable
private fun CeldaDia(dia: DiaAcopio) {
    val (texto, color) = when (dia.estado) {
        EstadoRecojo.REGISTRADO -> formatearLitros(dia.totalLitros).removeSuffix(" L") to Colores.brandText
        EstadoRecojo.SIN_RECOJO -> "SR" to Colores.advertencia
        EstadoRecojo.PENDIENTE -> "—" to Colores.textSecundario
    }
    val faltaSincronizar = dia.sincronizacion != null && dia.sincronizacion != EstadoSincronizacion.SINCRONIZADO
    Box(Modifier.width(ANCHO_DIA).height(ALTO_FILA), contentAlignment = Alignment.Center) {
        Text(
            if (faltaSincronizar) "$texto•" else texto,
            color = if (dia.sincronizacion == EstadoSincronizacion.ERROR) Colores.peligro else color,
            style = MaterialTheme.typography.bodyMedium,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

// ---------------------------------------------------------------------------------------------

/** Tarjeta de contexto del ciclo, presente en las dos vistas para no perder de vista dónde se está. */
@Composable
private fun TarjetaCiclo(ciclo: CicloAcopio) {
    Tarjeta(modifier = Modifier.border(1.dp, Colores.brand.copy(alpha = 0.4f), MaterialTheme.shapes.medium)) {
        Text(ciclo.titulo, color = Colores.brandText, style = MaterialTheme.typography.labelMedium)
        Spacer(Modifier.height(Espaciado.xxs))
        Text(ciclo.detalle, color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
    }
}

private fun fechaCorta(fecha: LocalDate): String = "${fecha.day.toString().padStart(2, '0')} ${mesCorto(fecha.monthNumber)}"

private fun mesCorto(mes: Int): String = when (mes) {
    1 -> "ene"; 2 -> "feb"; 3 -> "mar"; 4 -> "abr"; 5 -> "may"; 6 -> "jun"
    7 -> "jul"; 8 -> "ago"; 9 -> "sep"; 10 -> "oct"; 11 -> "nov"; else -> "dic"
}
