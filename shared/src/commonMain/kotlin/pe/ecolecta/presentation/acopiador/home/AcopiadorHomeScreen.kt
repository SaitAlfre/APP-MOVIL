package pe.ecolecta.presentation.acopiador.home

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.WaterDrop
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
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoSeguimiento
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSync
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DialogoMotivo
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearHora
import pe.ecolecta.presentation.design.formatearLitros
import pe.ecolecta.presentation.seguimiento.rememberSolicitadorPermisoUbicacion

@Composable
fun AcopiadorHomeScreen(
    alRegistrarEntrega: () -> Unit,
    alRegistrarLote: () -> Unit,
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
        EncabezadoSeccion("Hoy", subtitulo = "Resumen de tu jornada")

        Column(
            Modifier.padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica(
                    etiqueta = "Litros",
                    valor = formatearLitros(estado.litrosHoy),
                    modifier = Modifier.weight(1f),
                )
                TarjetaEstadistica(
                    etiqueta = "Entregas",
                    valor = estado.entregasHoy.toString(),
                    modifier = Modifier.weight(1f),
                )
                TarjetaEstadistica(
                    etiqueta = "Pendientes",
                    valor = estado.pendientesSync.toString(),
                    // Solo se tiñe cuando hay algo que enviar: en cero no debe pedir atención.
                    colorValor = if (estado.pendientesSync > 0) Colores.advertencia else Colores.textPrimary,
                    modifier = Modifier.weight(1f),
                )
            }

            if (estado.jornadaAbierta) {
                TarjetaSeguimiento(
                    estadoSeguimiento = estado.estadoSeguimiento,
                    mostrarAvisoPermisoDenegado = estado.mostrarAvisoPermisoDenegado,
                    onPermisoUbicacionResultado = viewModel::onPermisoUbicacionResultado,
                    onDetener = viewModel::detenerSeguimiento,
                    onDescartarAviso = viewModel::descartarAvisoPermiso,
                )
            }

            BotonPrimario(texto = "Registrar entrega", onClick = alRegistrarEntrega, icono = Icons.Filled.Add)
            BotonSecundario(texto = "Registrar lote", onClick = alRegistrarLote, icono = Icons.Filled.Inventory2)

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
                    textoAccion = "Registrar primera entrega",
                    alPresionarAccion = alRegistrarEntrega,
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
}

private fun puedeEditar(entrega: Entrega): Boolean = !entrega.anulada && entrega.syncState != SyncState.CONFLICT

/** Fila de entrega vista por el acopiador: manda de quién es, no cuántos litros. */
@Composable
private fun FilaEntregaDelDia(nombre: String, entrega: Entrega, onClick: (() -> Unit)?) {
    Tarjeta(onClick = onClick) {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s), verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Filled.Person, contentDescription = null, tint = Colores.brand, modifier = Modifier.size(20.dp))
                Column {
                    Text(nombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                    Text(
                        "${formatearLitros(entrega.litros)} · ${formatearHora(entrega.registradoEn)}",
                        style = MaterialTheme.typography.bodyMedium,
                        color = Colores.textSecundario,
                    )
                }
            }
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
            Text("Seguimiento de ubicación", style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
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
 * [BotonSecundario] a propósito — ese ocupa todo el ancho y compite con los botones de acción
 * principales de la pantalla.
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
                CampoTexto(litros, { litros = it }, "Litros", iconoInicial = Icons.Filled.WaterDrop)
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
