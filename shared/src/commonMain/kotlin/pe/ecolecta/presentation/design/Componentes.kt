package pe.ecolecta.presentation.design

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
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
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TextFieldColors
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Shape
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.em
import androidx.compose.ui.unit.sp

/** Forma de campos y botones de la web (`rounded-xl`). */
private val FormaControl = RoundedCornerShape(12.dp)

/** Sombra "tinta" de los botones principales de la web (`--eh-shadow-ink`). */
private fun Modifier.sombraTinta(forma: Shape, color: Color, activa: Boolean): Modifier =
    if (activa) shadow(10.dp, forma, clip = false, ambientColor = color.copy(alpha = 0.35f), spotColor = color.copy(alpha = 0.45f)) else this

/**
 * Antetítulo de la web (`text-xs font-semibold uppercase tracking-[.18em] text-eh-sage`): la
 * pequeña línea que va sobre los títulos de página y de sección.
 */
@Composable
fun Antetitulo(texto: String, modifier: Modifier = Modifier, color: Color? = null) {
    Text(
        texto.uppercase(),
        modifier = modifier,
        color = color ?: Colores.salvia,
        style = MaterialTheme.typography.labelMedium.copy(letterSpacing = 0.18.em),
    )
}

/**
 * Encabezado de página como `x-ui.page-header` de la web: antetítulo opcional en salvia, título
 * grande con tracking negativo y una acción opcional a la derecha.
 */
@Composable
fun EncabezadoSeccion(
    titulo: String,
    modifier: Modifier = Modifier,
    subtitulo: String? = null,
    accion: (@Composable () -> Unit)? = null,
    antetitulo: String? = null,
) {
    Row(
        modifier = modifier.fillMaxWidth().padding(horizontal = Espaciado.l, vertical = Espaciado.m).aparicionEscalonada(),
        horizontalArrangement = Arrangement.SpaceBetween,
        verticalAlignment = Alignment.CenterVertically,
    ) {
        Column(Modifier.weight(1f, fill = false)) {
            if (antetitulo != null) {
                Antetitulo(antetitulo)
                Spacer(Modifier.height(4.dp))
            }
            Text(titulo, style = MaterialTheme.typography.headlineMedium, color = Colores.textPrimary)
            if (subtitulo != null) {
                Spacer(Modifier.height(2.dp))
                Text(subtitulo, style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
            }
        }
        accion?.invoke()
    }
}

/** Botón cuadrado con borde de la cabecera web (tema, notificaciones, volver). */
@Composable
fun BotonIconoBorde(icono: ImageVector, descripcion: String, onClick: () -> Unit, modifier: Modifier = Modifier) {
    val interaccion = remember { MutableInteractionSource() }
    Box(
        modifier
            .size(40.dp)
            .efectoPresion(interaccion, 0.92f)
            .clip(FormaControl)
            .background(Colores.surface.copy(alpha = 0.65f))
            .border(1.dp, Colores.bordeFuerte, FormaControl)
            .clickable(interactionSource = interaccion, indication = null, onClick = onClick),
        contentAlignment = Alignment.Center,
    ) {
        Icon(icono, contentDescription = descripcion, tint = Colores.textPrimary, modifier = Modifier.size(18.dp))
    }
}

/**
 * Cabecera de las pantallas apiladas, como el header de la web: fondo crema translúcido, línea
 * inferior tenue y botón de retroceso cuadrado con borde.
 */
@Composable
fun BarraSuperior(
    titulo: String,
    modifier: Modifier = Modifier,
    alVolver: (() -> Unit)? = null,
    accion: (@Composable () -> Unit)? = null,
) {
    Column(modifier.fillMaxWidth().background(Colores.bgBase)) {
        Row(
            Modifier.fillMaxWidth().heightIn(min = 64.dp).padding(horizontal = Espaciado.m, vertical = Espaciado.xs),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            if (alVolver != null) {
                BotonIconoBorde(Icons.AutoMirrored.Filled.ArrowBack, "Volver", alVolver)
            }
            Text(
                titulo,
                style = MaterialTheme.typography.titleLarge,
                color = Colores.textPrimary,
                maxLines = 1,
                modifier = Modifier.weight(1f),
            )
            accion?.invoke()
        }
        DivisorSutil()
    }
}

/** Colores de campo de la web: borde tenue, foco salvia y fondo de superficie. */
@Composable
fun coloresCampo(): TextFieldColors = OutlinedTextFieldDefaults.colors(
    focusedBorderColor = Colores.salvia,
    unfocusedBorderColor = Colores.bordeFuerte,
    disabledBorderColor = Colores.borde,
    errorBorderColor = Colores.peligro,
    focusedContainerColor = Colores.surface,
    unfocusedContainerColor = Colores.surface,
    disabledContainerColor = Colores.surfaceAlta,
    errorContainerColor = Colores.surface,
    focusedLabelColor = Colores.salvia,
    unfocusedLabelColor = Colores.textSecundario,
    focusedLeadingIconColor = Colores.salvia,
    unfocusedLeadingIconColor = Colores.textSecundario,
    focusedTrailingIconColor = Colores.textPrimary,
    unfocusedTrailingIconColor = Colores.textSecundario,
    cursorColor = Colores.salvia,
    focusedTextColor = Colores.textPrimary,
    unfocusedTextColor = Colores.textPrimary,
)

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
            shape = FormaControl,
            colors = coloresCampo(),
            textStyle = MaterialTheme.typography.bodyLarge,
            leadingIcon = iconoInicial?.let { { Icon(it, contentDescription = null, modifier = Modifier.size(20.dp)) } },
            visualTransformation = if (esPin && !pinVisible) PasswordVisualTransformation() else VisualTransformation.None,
            keyboardOptions = if (esPin) KeyboardOptions(keyboardType = KeyboardType.NumberPassword) else KeyboardOptions.Default,
            trailingIcon = if (esPin) {
                {
                    IconButton(onClick = { pinVisible = !pinVisible }) {
                        Icon(
                            imageVector = if (pinVisible) Icons.Filled.VisibilityOff else Icons.Filled.Visibility,
                            contentDescription = if (pinVisible) "Ocultar PIN" else "Mostrar PIN",
                            modifier = Modifier.size(20.dp),
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

/** `x-ui.btn variant=primary` de la web: tinta, sombra tinta y leve hundimiento al presionar. */
@Composable
fun BotonPrimario(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    icono: ImageVector? = null,
    cargando: Boolean = false,
) {
    val interaccion = remember { MutableInteractionSource() }
    val activo = habilitado && !cargando
    Button(
        onClick = onClick,
        modifier = modifier
            .fillMaxWidth()
            .height(52.dp)
            .efectoPresion(interaccion)
            .sombraTinta(FormaControl, Colores.tinta, activo),
        enabled = activo,
        shape = FormaControl,
        interactionSource = interaccion,
        colors = ButtonDefaults.buttonColors(
            containerColor = Colores.brand,
            contentColor = Colores.onBrand,
            disabledContainerColor = Colores.brand.copy(alpha = 0.35f),
            disabledContentColor = Colores.onBrand.copy(alpha = 0.8f),
        ),
    ) {
        if (cargando) {
            CircularProgressIndicator(modifier = Modifier.size(20.dp), color = Colores.onBrand, strokeWidth = 2.dp)
        } else {
            if (icono != null) {
                Icon(icono, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(Modifier.width(Espaciado.xs))
            }
            Text(texto, style = MaterialTheme.typography.labelLarge.copy(fontSize = 14.sp))
        }
    }
}

/** `x-ui.btn variant=secondary` de la web: relleno gris-salvia suave, sin borde. */
@Composable
fun BotonSecundario(
    texto: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier,
    habilitado: Boolean = true,
    icono: ImageVector? = null,
) {
    val interaccion = remember { MutableInteractionSource() }
    Button(
        onClick = onClick,
        modifier = modifier.fillMaxWidth().height(52.dp).efectoPresion(interaccion),
        enabled = habilitado,
        shape = FormaControl,
        interactionSource = interaccion,
        elevation = null,
        colors = ButtonDefaults.buttonColors(
            containerColor = Colores.surfaceAlta,
            contentColor = Colores.textPrimary,
            disabledContainerColor = Colores.surfaceAlta.copy(alpha = 0.5f),
            disabledContentColor = Colores.textSecundario,
        ),
    ) {
        if (icono != null) {
            Icon(icono, contentDescription = null, modifier = Modifier.size(18.dp))
            Spacer(Modifier.width(Espaciado.xs))
        }
        Text(texto, style = MaterialTheme.typography.labelLarge.copy(fontSize = 14.sp))
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
    val interaccion = remember { MutableInteractionSource() }
    Button(
        onClick = onClick,
        modifier = modifier.height(40.dp).efectoPresion(interaccion).sombraTinta(FormaControl, Colores.tinta, true),
        shape = FormaControl,
        interactionSource = interaccion,
        contentPadding = PaddingValues(horizontal = Espaciado.m, vertical = Espaciado.xs),
        colors = ButtonDefaults.buttonColors(containerColor = Colores.brand, contentColor = Colores.onBrand),
    ) {
        if (icono != null) {
            Icon(icono, contentDescription = null, modifier = Modifier.size(16.dp))
            Spacer(Modifier.width(6.dp))
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
    val interaccion = remember { MutableInteractionSource() }
    OutlinedButton(
        onClick = onClick,
        modifier = modifier.fillMaxWidth().height(52.dp).efectoPresion(interaccion),
        shape = FormaControl,
        enabled = habilitado,
        interactionSource = interaccion,
        border = BorderStroke(1.dp, if (habilitado) color.copy(alpha = 0.4f) else Colores.borde),
        colors = ButtonDefaults.outlinedButtonColors(containerColor = Colores.surface.copy(alpha = 0.65f), contentColor = color),
    ) {
        if (cargando) {
            CircularProgressIndicator(modifier = Modifier.size(18.dp), color = color, strokeWidth = 2.dp)
        } else if (icono != null) {
            Icon(icono, contentDescription = null, modifier = Modifier.size(18.dp))
        }
        Spacer(Modifier.width(Espaciado.xs))
        Text(texto, style = MaterialTheme.typography.labelLarge.copy(fontSize = 14.sp))
    }
}

/** Texto de enlace/acción secundaria discreta (p. ej. "Ver todo", "Cerrar sesión"). */
@Composable
fun EnlaceTexto(texto: String, onClick: () -> Unit, modifier: Modifier = Modifier, color: Color? = null) {
    Text(
        texto,
        color = color ?: Colores.brandText,
        style = MaterialTheme.typography.labelLarge,
        modifier = modifier.clip(RoundedCornerShape(8.dp)).clickable(onClick = onClick).padding(vertical = Espaciado.xxs, horizontal = Espaciado.xxs),
    )
}

/**
 * `x-ui.card` de la web: superficie con borde tenue de 1 dp y radio 22, sin sombra. Entra con el
 * `rise` escalonado de la página y se hunde levemente al tocarla si es interactiva.
 */
@Composable
fun Tarjeta(
    modifier: Modifier = Modifier,
    onClick: (() -> Unit)? = null,
    padding: Dp = Espaciado.m,
    contenido: @Composable ColumnScope.() -> Unit,
) {
    val forma = MaterialTheme.shapes.large
    val interaccion = remember { MutableInteractionSource() }
    Surface(
        modifier = modifier
            .fillMaxWidth()
            .aparicionEscalonada()
            .let { if (onClick != null) it.efectoPresion(interaccion, 0.985f) else it }
            .clip(forma)
            .let { if (onClick != null) it.clickable(interactionSource = interaccion, indication = androidx.compose.material3.ripple(), onClick = onClick) else it },
        shape = forma,
        color = Colores.surface,
        border = BorderStroke(1.dp, Colores.borde),
    ) {
        Column(Modifier.padding(padding), content = contenido)
    }
}

/** Fondo suave del recuadro de ícono de un KPI según su color de acento (`x-ui.kpi` de la web). */
@Composable
private fun fondoSuaveDe(acento: Color): Color = when (acento) {
    Colores.exito, Colores.brandText -> Colores.exitoSuave
    Colores.info -> Colores.infoSuave
    Colores.advertencia, Colores.secundario -> Colores.advertenciaSuave
    Colores.peligro -> Colores.peligroSuave
    Colores.violeta -> Colores.violetaSuave
    Colores.brand -> Colores.brandContainer
    else -> acento.copy(alpha = 0.14f)
}

/**
 * Tarjeta de estadística con el formato `x-ui.kpi` de la web: etiqueta pequeña arriba, recuadro
 * de ícono tintado a la derecha y la cifra grande con tracking negativo.
 *
 * Con [iconoEnLinea] el ícono va suelto a la izquierda de la etiqueta (métricas compactas de
 * móvil). [colorValor] tiñe la cifra cuando esa métrica es la protagonista de la pantalla.
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
    estiloValor: TextStyle? = null,
) {
    val acento = color ?: Colores.exito
    val forma = MaterialTheme.shapes.medium
    Surface(
        modifier = modifier.fillMaxWidth().aparicionEscalonada(),
        shape = forma,
        color = Colores.surface,
        border = BorderStroke(1.dp, Colores.borde),
    ) {
        Column(Modifier.padding(Espaciado.m)) {
            Row(
                verticalAlignment = if (iconoEnLinea) Alignment.CenterVertically else Alignment.Top,
                horizontalArrangement = if (iconoEnLinea) Arrangement.spacedBy(Espaciado.xs) else Arrangement.SpaceBetween,
                modifier = Modifier.fillMaxWidth(),
            ) {
                if (iconoEnLinea && icono != null) {
                    Icon(icono, contentDescription = null, tint = acento, modifier = Modifier.size(16.dp))
                }
                Text(
                    etiqueta,
                    style = MaterialTheme.typography.bodySmall,
                    fontWeight = FontWeight.Medium,
                    color = Colores.textSecundario,
                    modifier = Modifier.weight(1f),
                )
                if (!iconoEnLinea && icono != null) {
                    Box(
                        Modifier.size(28.dp).clip(RoundedCornerShape(8.dp)).background(fondoSuaveDe(acento)),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(icono, contentDescription = null, tint = acento, modifier = Modifier.size(16.dp))
                    }
                }
            }
            Spacer(Modifier.height(if (iconoEnLinea) Espaciado.xs else Espaciado.m))
            Text(
                valor,
                style = estiloValor ?: MaterialTheme.typography.headlineMedium.copy(letterSpacing = (-0.04).em),
                color = colorValor ?: Colores.textPrimary,
                fontWeight = FontWeight.SemiBold,
                maxLines = 1,
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
    ChipEstado("$cantidad pendientes", Colores.advertencia, modifier.aparicionPop())
}

/**
 * `x-ui.badge` de la web: pastilla con fondo suave del mismo tono, punto del color del texto y
 * letra pequeña en semibold.
 */
@Composable
fun ChipEstado(texto: String, color: Color, modifier: Modifier = Modifier, mostrarPunto: Boolean = true) {
    Row(
        modifier
            .clip(RoundedCornerShape(50))
            .background(fondoSuaveDe(color))
            .padding(horizontal = 10.dp, vertical = 5.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (mostrarPunto) {
            Box(Modifier.size(5.dp).clip(CircleShape).background(color))
        }
        Text(texto, color = color, style = MaterialTheme.typography.labelMedium, maxLines = 1)
    }
}

/**
 * Chip de filtro/selección, como las pestañas de periodo de la web: la opción activa se rellena de
 * tinta y el cambio de color se anima.
 */
@Composable
fun ChipSeleccionable(texto: String, seleccionado: Boolean, modifier: Modifier = Modifier, onClick: () -> Unit) {
    val fondo by animateColorAsState(if (seleccionado) Colores.brand else Colores.surface, tween(220, easing = CurvaLumen), label = "chipFondo")
    val tinta by animateColorAsState(if (seleccionado) Colores.onBrand else Colores.textSecundario, tween(220, easing = CurvaLumen), label = "chipTexto")
    val borde by animateColorAsState(if (seleccionado) Colores.brand else Colores.bordeFuerte, tween(220, easing = CurvaLumen), label = "chipBorde")
    val interaccion = remember { MutableInteractionSource() }
    Surface(
        modifier = modifier
            .efectoPresion(interaccion, 0.95f)
            .clip(RoundedCornerShape(50))
            .clickable(interactionSource = interaccion, indication = androidx.compose.material3.ripple(), onClick = onClick),
        shape = RoundedCornerShape(50),
        color = fondo,
        contentColor = tinta,
        border = BorderStroke(1.dp, borde),
    ) {
        Text(
            texto,
            style = MaterialTheme.typography.labelLarge,
            modifier = Modifier.padding(horizontal = 14.dp, vertical = 8.dp),
        )
    }
}

enum class TipoBanner { EXITO, ERROR, ADVERTENCIA, INFO }

/** `x-ui.alert` de la web: fondo suave, borde del mismo tono y el ícono en un círculo. Entra con `pop`. */
@Composable
fun Banner(mensaje: String, tipo: TipoBanner, modifier: Modifier = Modifier) {
    val (color, icono) = when (tipo) {
        TipoBanner.EXITO -> Colores.exito to Icons.Filled.CheckCircle
        TipoBanner.ERROR -> Colores.peligro to Icons.Filled.ErrorOutline
        TipoBanner.ADVERTENCIA -> Colores.advertencia to Icons.Filled.WarningAmber
        TipoBanner.INFO -> Colores.info to Icons.Filled.Info
    }
    val forma = RoundedCornerShape(16.dp)
    Surface(
        modifier = modifier.fillMaxWidth().aparicionPop(),
        shape = forma,
        color = fondoSuaveDe(color),
        border = BorderStroke(1.dp, color.copy(alpha = 0.2f)),
    ) {
        Row(
            Modifier.padding(horizontal = Espaciado.m, vertical = Espaciado.s),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            Box(Modifier.size(26.dp).clip(CircleShape).background(color.copy(alpha = 0.12f)), contentAlignment = Alignment.Center) {
                Icon(icono, contentDescription = null, tint = color, modifier = Modifier.size(15.dp))
            }
            Text(mensaje, color = color, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium, modifier = Modifier.weight(1f))
        }
    }
}

/** `x-ui.empty` de la web: recuadro de ícono salvia, título y descripción centrados, acción opcional. */
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
        modifier.fillMaxWidth().padding(horizontal = Espaciado.xl, vertical = Espaciado.xxxl).aparicionEscalonada(),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        if (icono != null) {
            Box(
                Modifier.size(48.dp).clip(RoundedCornerShape(16.dp)).background(Colores.surfaceAlta),
                contentAlignment = Alignment.Center,
            ) {
                Icon(icono, contentDescription = null, tint = Colores.salvia, modifier = Modifier.size(24.dp))
            }
            Spacer(Modifier.height(Espaciado.m))
        }
        Text(titulo, color = Colores.textPrimary, style = MaterialTheme.typography.titleSmall, textAlign = TextAlign.Center)
        if (descripcion != null) {
            Spacer(Modifier.height(Espaciado.xxs))
            Text(descripcion, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall, textAlign = TextAlign.Center)
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
            CircularProgressIndicator(color = Colores.salvia, trackColor = Colores.surfaceAlta, strokeWidth = 3.dp, modifier = Modifier.size(36.dp))
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
        containerColor = Colores.surface,
        title = { Text(titulo, style = MaterialTheme.typography.titleLarge, color = Colores.textPrimary) },
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
            ) { Text(textoConfirmar, color = if (motivo.isNotBlank() && !enviando) Colores.textPrimary else Colores.textSecundario) }
        },
        dismissButton = { TextButton(onClick = onCancelar, enabled = !enviando) { Text("Cancelar", color = Colores.textSecundario) } },
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
            Box(
                Modifier.size(32.dp).clip(RoundedCornerShape(10.dp)).background(Colores.surfaceAlta),
                contentAlignment = Alignment.Center,
            ) {
                Icon(icono, contentDescription = null, tint = Colores.salvia, modifier = Modifier.size(17.dp))
            }
        }
        Column {
            Text(etiqueta, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
            Text(valor, color = Colores.textPrimary, style = MaterialTheme.typography.bodyLarge, fontWeight = FontWeight.SemiBold)
        }
    }
}

/** Campo de búsqueda redondeado con lupa (`x-ui.search` de la web), para filtrar listas largas. */
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
        placeholder = { Text(marcador, style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario.copy(alpha = 0.7f)) },
        leadingIcon = { Icon(Icons.Filled.Search, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.size(19.dp)) },
        singleLine = true,
        textStyle = MaterialTheme.typography.bodyMedium,
        shape = FormaControl,
        colors = coloresCampo(),
    )
}
