package pe.ecolecta.presentation.design

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Info
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.foundation.text.KeyboardOptions

/**
 * Encabezado estándar de sección/pantalla: título con jerarquía tipográfica clara y una acción
 * opcional a la derecha (botón, filtro, etc.). Usado por ADMIN, ACOPIADOR y PROVEEDOR por igual.
 */
@Composable
fun EncabezadoSeccion(
    titulo: String,
    modifier: Modifier = Modifier,
    subtitulo: String? = null,
    accion: (@Composable () -> Unit)? = null,
) {
    Row(
        modifier = modifier.fillMaxWidth().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Column(Modifier.weight(1f, fill = false)) {
            Text(titulo, style = MaterialTheme.typography.headlineSmall, color = Colores.textPrimary)
            if (subtitulo != null) {
                Spacer(Modifier.height(2.dp))
                Text(subtitulo, style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
            }
        }
        accion?.invoke()
    }
}

/** Barra superior con flecha de retroceso, para pantallas de detalle/formulario apiladas sobre un tab. */
@Composable
fun BarraSuperior(
    titulo: String,
    modifier: Modifier = Modifier,
    alVolver: (() -> Unit)? = null,
    accion: (@Composable () -> Unit)? = null,
) {
    Surface(color = Colores.surface, modifier = modifier.fillMaxWidth()) {
        Row(
            Modifier.fillMaxWidth().padding(horizontal = Espaciado.xs, vertical = Espaciado.xxs),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            if (alVolver != null) {
                IconButton(onClick = alVolver) {
                    Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver", tint = Colores.textPrimary)
                }
            } else {
                Spacer(Modifier.width(Espaciado.m))
            }
            Text(
                titulo,
                style = MaterialTheme.typography.titleLarge,
                color = Colores.textPrimary,
                modifier = Modifier.weight(1f).padding(vertical = Espaciado.s),
            )
            accion?.invoke()
            Spacer(Modifier.width(Espaciado.xs))
        }
    }
}

@Composable
fun CampoTexto(
    valor: String,
    onValorCambia: (String) -> Unit,
    etiqueta: String,
    modifier: Modifier = Modifier,
    error: String? = null,
    soloLectura: Boolean = false,
    iconoInicial: ImageVector? = null,
    ayuda: String? = null,
    esPin: Boolean = false,
) {
    // Estado propio del campo: cada CampoTexto oculta su PIN de forma independiente y siempre vuelve
    // a ocultarlo si el campo se recompone limpio (nunca se filtra a otros campos ni queda "pegado").
    var pinVisible by remember { mutableStateOf(false) }
    Column(modifier) {
        OutlinedTextField(
            value = valor,
            onValueChange = onValorCambia,
            label = { Text(etiqueta) },
            modifier = Modifier.fillMaxWidth(),
            isError = error != null,
            readOnly = soloLectura,
            singleLine = true,
            shape = MaterialTheme.shapes.small,
            leadingIcon = iconoInicial?.let { { Icon(it, contentDescription = null) } },
            visualTransformation = if (esPin && !pinVisible) PasswordVisualTransformation() else VisualTransformation.None,
            keyboardOptions = if (esPin) KeyboardOptions(keyboardType = KeyboardType.NumberPassword) else KeyboardOptions.Default,
            trailingIcon = if (esPin) {
                {
                    IconButton(onClick = { pinVisible = !pinVisible }) {
                        Icon(
                            imageVector = if (pinVisible) Icons.Filled.VisibilityOff else Icons.Filled.Visibility,
                            contentDescription = if (pinVisible) "Ocultar PIN" else "Mostrar PIN",
                        )
                    }
                }
            } else {
                null
            },
            supportingText = when {
                error != null -> ({ Text(error, color = Colores.peligro) })
                ayuda != null -> ({ Text(ayuda, color = Colores.textSecundario) })
                else -> null
            },
        )
    }
}

@Composable
fun BotonPrimario(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    icono: ImageVector? = null,
    cargando: Boolean = false,
) {
    Button(
        onClick = onClick,
        modifier = modifier.fillMaxWidth().height(52.dp),
        enabled = habilitado && !cargando,
        shape = MaterialTheme.shapes.medium,
        colors = ButtonDefaults.buttonColors(containerColor = Colores.brand, contentColor = Colores.onBrand),
    ) {
        if (cargando) {
            CircularProgressIndicator(modifier = Modifier.size(22.dp), color = Colores.onBrand, strokeWidth = 2.dp)
        } else {
            if (icono != null) {
                Icon(icono, contentDescription = null, modifier = Modifier.size(20.dp))
                Spacer(Modifier.width(Espaciado.xs))
            }
            Text(texto, style = MaterialTheme.typography.titleMedium)
        }
    }
}

@Composable
fun BotonSecundario(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    icono: ImageVector? = null,
) {
    OutlinedButton(
        onClick = onClick,
        modifier = modifier.fillMaxWidth().height(52.dp),
        enabled = habilitado,
        shape = MaterialTheme.shapes.medium,
    ) {
        if (icono != null) {
            Icon(icono, contentDescription = null, modifier = Modifier.size(20.dp))
            Spacer(Modifier.width(Espaciado.xs))
        }
        Text(texto, style = MaterialTheme.typography.titleMedium)
    }
}

/**
 * Botón compacto (ancho según su contenido, NO fillMaxWidth) para acciones dentro de un encabezado
 * o una fila (p. ej. "+ Nuevo" en [EncabezadoSeccion]). A diferencia de [BotonPrimario], es seguro
 * usarlo como hijo no ponderado de un `Row`: nunca reclama todo el ancho disponible y por lo tanto
 * no le quita espacio al título/subtítulo vecino.
 */
@Composable
fun BotonAccion(texto: String, onClick: () -> Unit, modifier: Modifier = Modifier, icono: ImageVector? = null) {
    Button(
        onClick = onClick,
        modifier = modifier.height(40.dp),
        shape = MaterialTheme.shapes.medium,
        contentPadding = PaddingValues(horizontal = Espaciado.m, vertical = Espaciado.xs),
        colors = ButtonDefaults.buttonColors(containerColor = Colores.brand, contentColor = Colores.onBrand),
    ) {
        if (icono != null) {
            Icon(icono, contentDescription = null, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(Espaciado.xxs))
        }
        Text(texto, style = MaterialTheme.typography.labelLarge)
    }
}

/**
 * Botón de borde teñido para acciones de salida/cierre (cerrar jornada, cerrar sesión): van
 * delineados y no rellenos porque casi nunca son la acción que uno viene a hacer a esa pantalla.
 */
@Composable
fun BotonBorde(
    texto: String,
    color: Color,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    icono: ImageVector? = null,
    habilitado: Boolean = true,
    cargando: Boolean = false,
) {
    OutlinedButton(
        onClick = onClick,
        modifier = modifier.fillMaxWidth().height(52.dp),
        shape = MaterialTheme.shapes.medium,
        enabled = habilitado,
        colors = ButtonDefaults.outlinedButtonColors(contentColor = color),
    ) {
        if (cargando) {
            CircularProgressIndicator(modifier = Modifier.size(20.dp), color = color, strokeWidth = 2.dp)
        } else if (icono != null) {
            Icon(icono, contentDescription = null, modifier = Modifier.size(20.dp))
        }
        Spacer(Modifier.width(Espaciado.xs))
        Text(texto, style = MaterialTheme.typography.titleMedium)
    }
}

/** Texto de enlace/acción secundaria discreta (p. ej. "Ver todo", "Cerrar sesión"). */
@Composable
fun EnlaceTexto(texto: String, onClick: () -> Unit, modifier: Modifier = Modifier, color: Color? = null) {
    Text(
        texto,
        color = color ?: Colores.brandText,
        style = MaterialTheme.typography.labelLarge,
        modifier = modifier.clickable(onClick = onClick).padding(vertical = Espaciado.xxs, horizontal = Espaciado.xxs),
    )
}

/** Contenedor tipo tarjeta consistente (elevación sutil, esquinas redondeadas, padding interno estándar). */
@Composable
fun Tarjeta(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    padding: androidx.compose.ui.unit.Dp = Espaciado.m,
    contenido: @Composable androidx.compose.foundation.layout.ColumnScope.() -> Unit,
) {
    Surface(
        modifier = if (onClick != null) {
            modifier.fillMaxWidth().clip(MaterialTheme.shapes.medium).clickable(onClick = onClick)
        } else {
            modifier.fillMaxWidth()
        },
        shape = MaterialTheme.shapes.medium,
        color = Colores.surface,
        tonalElevation = 1.dp,
        shadowElevation = 1.dp,
    ) {
        Column(Modifier.padding(padding), content = contenido)
    }
}

/**
 * Tarjeta de estadística para dashboards (ADMIN/ACOPIADOR/PROVEEDOR): valor grande + etiqueta +
 * ícono de color.
 *
 * Admite las dos presentaciones del diseño con una sola definición: por defecto el ícono va a la
 * derecha dentro de un círculo tenue (tableros densos de ADMIN), y con [iconoEnLinea] va suelto a
 * la izquierda de la etiqueta, que es como se ven las métricas en las pantallas de móvil.
 * [colorValor] tiñe la cifra cuando esa métrica es la protagonista de la pantalla — el resto de
 * las tarjetas dejan el valor en el color de texto normal para no competir entre sí.
 */
@Composable
fun TarjetaEstadistica(
    etiqueta: String,
    valor: String,
    modifier: Modifier = Modifier,
    icono: ImageVector? = null,
    color: Color? = null,
    colorValor: Color? = null,
    iconoEnLinea: Boolean = false,
    estiloValor: androidx.compose.ui.text.TextStyle? = null,
) {
    val acento = color ?: Colores.brand
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = Colores.surface,
        tonalElevation = 1.dp,
        shadowElevation = 1.dp,
    ) {
        Column(Modifier.padding(Espaciado.m)) {
            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = if (iconoEnLinea) Arrangement.spacedBy(Espaciado.xs) else Arrangement.SpaceBetween,
                modifier = Modifier.fillMaxWidth(),
            ) {
                if (iconoEnLinea && icono != null) {
                    Icon(icono, contentDescription = null, tint = acento, modifier = Modifier.size(18.dp))
                }
                Text(etiqueta, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario, modifier = Modifier.weight(1f))
                if (!iconoEnLinea && icono != null) {
                    Box(
                        Modifier.size(28.dp).clip(CircleShape).background(acento.copy(alpha = 0.14f)),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(icono, contentDescription = null, tint = acento, modifier = Modifier.size(16.dp))
                    }
                }
            }
            Spacer(Modifier.height(Espaciado.xxs))
            Text(
                valor,
                style = estiloValor ?: MaterialTheme.typography.headlineMedium,
                color = colorValor ?: Colores.textPrimary,
                fontWeight = FontWeight.Bold,
            )
        }
    }
}

/**
 * Pastilla ámbar con la cuenta de pendientes por sincronizar, para el encabezado de cualquier
 * pantalla del Acopiador (Jornada, Proveedores, Entregas, Sincronizar, Perfil): siempre visible,
 * no solo dentro de la pestaña de sincronización.
 */
@Composable
fun PildoraPendientes(cantidad: Int, modifier: Modifier = Modifier) {
    if (cantidad <= 0) return
    Row(
        modifier
            .clip(RoundedCornerShape(50))
            .background(Colores.advertencia.copy(alpha = 0.14f))
            .padding(horizontal = Espaciado.s, vertical = Espaciado.xxs),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Box(Modifier.size(6.dp).clip(CircleShape).background(Colores.advertencia))
        Text("$cantidad pendientes", color = Colores.advertencia, style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.Bold)
    }
}

/**
 * Insignia de estado (SYNCED/PENDING/ERROR/CONFLICT, ACTIVO/INACTIVO, etc.): pastilla con fondo
 * tenue y texto del mismo color. [mostrarPunto] antepone un punto sólido; las pantallas de ADMIN
 * lo usan para distinguir estados de un vistazo en tablas densas, mientras que ACOPIADOR y
 * PROVEEDOR lo omiten siguiendo el diseño de móvil, donde la pastilla ya va suelta y aireada.
 */
@Composable
fun ChipEstado(texto: String, color: Color, modifier: Modifier = Modifier, mostrarPunto: Boolean = true) {
    Row(
        modifier
            .clip(RoundedCornerShape(50))
            .background(color.copy(alpha = 0.14f))
            .padding(horizontal = Espaciado.s, vertical = Espaciado.xxs),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (mostrarPunto) {
            Box(Modifier.size(6.dp).clip(CircleShape).background(color))
        }
        Text(texto, color = color, style = MaterialTheme.typography.labelMedium, fontWeight = FontWeight.SemiBold)
    }
}

/** Chip de filtro/selección (única elección entre varias opciones: zona, estado, rol, etc.). */
@Composable
fun ChipSeleccionable(texto: String, seleccionado: Boolean, modifier: Modifier = Modifier, onClick: () -> Unit) {
    Surface(
        modifier = modifier.clip(RoundedCornerShape(50)).clickable(onClick = onClick),
        shape = RoundedCornerShape(50),
        color = if (seleccionado) Colores.brand else Colores.surfaceAlta,
        contentColor = if (seleccionado) Colores.onBrand else Colores.textSecundario,
    ) {
        Text(
            texto,
            style = MaterialTheme.typography.labelLarge,
            modifier = Modifier.padding(horizontal = Espaciado.m, vertical = Espaciado.xs),
        )
    }
}

enum class TipoBanner { EXITO, ERROR, ADVERTENCIA, INFO }

/** Mensaje de éxito/error/advertencia/información en línea (formularios, listas, resultados de acciones). */
@Composable
fun Banner(mensaje: String, tipo: TipoBanner, modifier: Modifier = Modifier) {
    val (color, icono) = when (tipo) {
        TipoBanner.EXITO -> Colores.exito to Icons.Filled.CheckCircle
        TipoBanner.ERROR -> Colores.peligro to Icons.Filled.ErrorOutline
        TipoBanner.ADVERTENCIA -> Colores.advertencia to Icons.Filled.WarningAmber
        TipoBanner.INFO -> Colores.info to Icons.Filled.Info
    }
    Surface(
        modifier = modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.small,
        color = color.copy(alpha = 0.12f),
    ) {
        Row(
            Modifier.padding(horizontal = Espaciado.m, vertical = Espaciado.s),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Espaciado.xs),
        ) {
            Icon(icono, contentDescription = null, tint = color, modifier = Modifier.size(20.dp))
            Text(mensaje, color = color, style = MaterialTheme.typography.bodyMedium, modifier = Modifier.weight(1f))
        }
    }
}

/** Estado vacío simple, mantiene la firma histórica usada en ~15 listas. */
/** Estado vacío enriquecido: ícono + título + descripción opcional + acción opcional (CTA). */
@Composable
fun EstadoVacio(
    titulo: String,
    modifier: Modifier = Modifier,
    descripcion: String? = null,
    icono: ImageVector? = null,
    textoAccion: String? = null,
    alPresionarAccion: (() -> Unit)? = null,
) {
    Column(
        modifier.fillMaxWidth().padding(Espaciado.xxl),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        if (icono != null) {
            Box(
                Modifier.size(56.dp).clip(CircleShape).background(Colores.surfaceAlta),
                contentAlignment = Alignment.Center,
            ) {
                Icon(icono, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.size(28.dp))
            }
            Spacer(Modifier.height(Espaciado.m))
        }
        Text(titulo, color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium, textAlign = TextAlign.Center)
        if (descripcion != null) {
            Spacer(Modifier.height(Espaciado.xxs))
            Text(descripcion, color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium, textAlign = TextAlign.Center)
        }
        if (textoAccion != null && alPresionarAccion != null) {
            Spacer(Modifier.height(Espaciado.m))
            BotonSecundario(texto = textoAccion, onClick = alPresionarAccion, modifier = Modifier.width(220.dp))
        }
    }
}

/** Indicador de carga a pantalla completa (o dentro de un contenedor), con etiqueta opcional. */
@Composable
fun IndicadorCarga(modifier: Modifier = Modifier, mensaje: String? = null) {
    Box(modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Column(horizontalAlignment = Alignment.CenterHorizontally) {
            CircularProgressIndicator(color = Colores.brand)
            if (mensaje != null) {
                Spacer(Modifier.height(Espaciado.s))
                Text(mensaje, color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
            }
        }
    }
}

/** Diálogo reusado por Corregir/Anular/Rechazar/Resolver: toda acción crítica exige un motivo (§40). */
@Composable
fun DialogoMotivo(
    titulo: String,
    etiquetaCampo: String = "Motivo",
    textoConfirmar: String = "Confirmar",
    onConfirmar: (String) -> Unit,
    onCancelar: () -> Unit,
    contenidoExtra: @Composable (() -> Unit)? = null,
) {
    var motivo by remember { mutableStateOf("") }
    // Este diálogo se reutiliza para acciones de negocio sensibles (Corregir/Anular/Rechazar/
    // Resolver, no idempotentes): sin este guard, un doble tap accidental sobre "Confirmar" podría
    // invocar onConfirmar dos veces antes de que el llamador cierre el diálogo.
    var enviando by remember { mutableStateOf(false) }
    AlertDialog(
        onDismissRequest = onCancelar,
        shape = MaterialTheme.shapes.large,
        title = { Text(titulo, style = MaterialTheme.typography.titleLarge) },
        text = {
            Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                contenidoExtra?.invoke()
                CampoTexto(valor = motivo, onValorCambia = { motivo = it }, etiqueta = etiquetaCampo)
            }
        },
        confirmButton = {
            TextButton(
                onClick = { enviando = true; onConfirmar(motivo) },
                enabled = motivo.isNotBlank() && !enviando,
            ) { Text(textoConfirmar) }
        },
        dismissButton = { TextButton(onClick = onCancelar, enabled = !enviando) { Text("Cancelar") } },
    )
}

@Composable
fun DivisorSutil(modifier: Modifier = Modifier) {
    Box(modifier.fillMaxWidth().height(1.dp).background(Colores.borde))
}

/**
 * Par etiqueta/valor apilado, el bloque de lectura más repetido de la app (detalle de entrega,
 * perfil, QR, ruta). Vivía duplicado como `private fun Dato` en media docena de pantallas, cada
 * una con su propio espaciado; aquí queda una sola definición para que todas midan igual.
 */
@Composable
fun Dato(
    etiqueta: String,
    valor: String,
    modifier: Modifier = Modifier,
    icono: ImageVector? = null,
    ultimo: Boolean = false,
) {
    Row(
        modifier.fillMaxWidth().padding(bottom = if (ultimo) 0.dp else Espaciado.s),
        horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
    ) {
        if (icono != null) {
            Icon(icono, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.padding(top = 2.dp).size(20.dp))
        }
        Column {
            Text(etiqueta, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
            Text(valor, color = Colores.textPrimary, style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.SemiBold)
        }
    }
}

/** Campo de búsqueda redondeado con lupa, para filtrar listas largas (proveedores, entregas…). */
@Composable
fun CampoBusqueda(
    valor: String,
    onValorCambia: (String) -> Unit,
    marcador: String,
    modifier: Modifier = Modifier,
) {
    OutlinedTextField(
        value = valor,
        onValueChange = onValorCambia,
        modifier = modifier.fillMaxWidth(),
        placeholder = { Text(marcador, style = MaterialTheme.typography.bodyMedium) },
        leadingIcon = { Icon(Icons.Filled.Search, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.size(20.dp)) },
        singleLine = true,
        shape = MaterialTheme.shapes.medium,
    )
}
