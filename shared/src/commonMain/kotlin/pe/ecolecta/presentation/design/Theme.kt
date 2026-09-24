package pe.ecolecta.presentation.design

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.em
import androidx.compose.ui.unit.sp
import org.jetbrains.compose.resources.Font
import pe.ecolecta.shared.resources.Res
import pe.ecolecta.shared.resources.inter_400
import pe.ecolecta.shared.resources.inter_500
import pe.ecolecta.shared.resources.inter_600
import pe.ecolecta.shared.resources.inter_700
import pe.ecolecta.shared.resources.inter_800

/**
 * Radios del panel web: campos y botones `rounded-xl` (12), tarjetas `rounded-[20px]`/`[22px]`
 * y diálogos amplios.
 */
val EcolectaShapes = Shapes(
    extraSmall = RoundedCornerShape(8.dp),
    small = RoundedCornerShape(12.dp),
    medium = RoundedCornerShape(20.dp),
    large = RoundedCornerShape(22.dp),
    extraLarge = RoundedCornerShape(28.dp),
)

/** Inter, la misma tipografía del panel web. */
@Composable
fun familiaInter(): FontFamily = FontFamily(
    Font(Res.font.inter_400, FontWeight.Normal),
    Font(Res.font.inter_500, FontWeight.Medium),
    Font(Res.font.inter_600, FontWeight.SemiBold),
    Font(Res.font.inter_700, FontWeight.Bold),
    Font(Res.font.inter_800, FontWeight.ExtraBold),
)

/**
 * Escala tipográfica de la web: titulares en semibold con tracking negativo (`tracking-[-.045em]`),
 * cuerpo compacto y etiquetas pequeñas en semibold.
 */
fun tipografiaEcolecta(inter: FontFamily) = Typography(
    displaySmall = TextStyle(fontFamily = inter, fontSize = 34.sp, fontWeight = FontWeight.SemiBold, lineHeight = 40.sp, letterSpacing = (-0.045).em),
    headlineLarge = TextStyle(fontFamily = inter, fontSize = 30.sp, fontWeight = FontWeight.SemiBold, lineHeight = 36.sp, letterSpacing = (-0.045).em),
    headlineMedium = TextStyle(fontFamily = inter, fontSize = 26.sp, fontWeight = FontWeight.SemiBold, lineHeight = 32.sp, letterSpacing = (-0.04).em),
    headlineSmall = TextStyle(fontFamily = inter, fontSize = 22.sp, fontWeight = FontWeight.SemiBold, lineHeight = 28.sp, letterSpacing = (-0.035).em),
    titleLarge = TextStyle(fontFamily = inter, fontSize = 19.sp, fontWeight = FontWeight.SemiBold, lineHeight = 25.sp, letterSpacing = (-0.025).em),
    titleMedium = TextStyle(fontFamily = inter, fontSize = 15.sp, fontWeight = FontWeight.SemiBold, lineHeight = 21.sp, letterSpacing = (-0.015).em),
    titleSmall = TextStyle(fontFamily = inter, fontSize = 13.sp, fontWeight = FontWeight.SemiBold, lineHeight = 19.sp, letterSpacing = (-0.01).em),
    bodyLarge = TextStyle(fontFamily = inter, fontSize = 15.sp, fontWeight = FontWeight.Normal, lineHeight = 22.sp, letterSpacing = (-0.005).em),
    bodyMedium = TextStyle(fontFamily = inter, fontSize = 13.sp, fontWeight = FontWeight.Normal, lineHeight = 19.sp),
    bodySmall = TextStyle(fontFamily = inter, fontSize = 12.sp, fontWeight = FontWeight.Normal, lineHeight = 17.sp),
    labelLarge = TextStyle(fontFamily = inter, fontSize = 13.sp, fontWeight = FontWeight.SemiBold, lineHeight = 18.sp),
    labelMedium = TextStyle(fontFamily = inter, fontSize = 11.sp, fontWeight = FontWeight.SemiBold, lineHeight = 15.sp),
    labelSmall = TextStyle(fontFamily = inter, fontSize = 10.sp, fontWeight = FontWeight.SemiBold, lineHeight = 13.sp, letterSpacing = 0.04.em),
)

private fun esquemaClaro(paleta: PaletaEcolecta) = lightColorScheme(
    primary = paleta.brand,
    onPrimary = paleta.onBrand,
    primaryContainer = paleta.brandContainer,
    onPrimaryContainer = paleta.onBrandContainer,
    secondary = paleta.salvia,
    onSecondary = paleta.onBrand,
    secondaryContainer = paleta.lima,
    onSecondaryContainer = paleta.tinta,
    tertiary = paleta.info,
    onTertiary = paleta.onBrand,
    background = paleta.bgBase,
    onBackground = paleta.textPrimary,
    surface = paleta.surface,
    onSurface = paleta.textPrimary,
    surfaceVariant = paleta.surfaceAlta,
    onSurfaceVariant = paleta.textSecundario,
    surfaceTint = paleta.surface,
    surfaceContainerLowest = paleta.surface,
    surfaceContainerLow = paleta.surface,
    surfaceContainer = paleta.surface,
    surfaceContainerHigh = paleta.surface,
    surfaceContainerHighest = paleta.surfaceAlta,
    outline = paleta.bordeFuerte,
    outlineVariant = paleta.borde,
    error = paleta.peligro,
    onError = paleta.onPeligro,
    errorContainer = paleta.peligroSuave,
    onErrorContainer = paleta.peligro,
)

private fun esquemaOscuro(paleta: PaletaEcolecta) = darkColorScheme(
    primary = paleta.brand,
    onPrimary = paleta.onBrand,
    primaryContainer = paleta.brandContainer,
    onPrimaryContainer = paleta.onBrandContainer,
    secondary = paleta.salvia,
    onSecondary = paleta.tinta,
    secondaryContainer = paleta.lima,
    onSecondaryContainer = paleta.tinta,
    tertiary = paleta.info,
    onTertiary = paleta.tinta,
    background = paleta.bgBase,
    onBackground = paleta.textPrimary,
    surface = paleta.surface,
    onSurface = paleta.textPrimary,
    surfaceVariant = paleta.surfaceAlta,
    onSurfaceVariant = paleta.textSecundario,
    surfaceTint = paleta.surface,
    surfaceContainerLowest = paleta.bgBase,
    surfaceContainerLow = paleta.surface,
    surfaceContainer = paleta.surface,
    surfaceContainerHigh = paleta.surfaceAlta,
    surfaceContainerHighest = paleta.surfaceAlta,
    outline = paleta.bordeFuerte,
    outlineVariant = paleta.borde,
    error = paleta.peligro,
    onError = paleta.onPeligro,
    errorContainer = paleta.peligroSuave,
    onErrorContainer = paleta.peligro,
)

/**
 * Punto de entrada único del Design System de Ecolecta Huata. Envuelve [MaterialTheme] con la
 * paleta del panel web (clara u oscura según el sistema), la tipografía Inter y los radios de la
 * web, y expone la paleta activa a [Colores] vía [LocalPaletaEcolecta].
 */
@Composable
fun EcolectaTheme(oscuroForzado: Boolean? = null, content: @Composable () -> Unit) {
    val esOscuro = oscuroForzado ?: isSystemInDarkTheme()
    val paleta = if (esOscuro) PaletaOscura else PaletaClara
    val colorScheme = if (esOscuro) esquemaOscuro(paleta) else esquemaClaro(paleta)

    CompositionLocalProvider(LocalPaletaEcolecta provides paleta) {
        MaterialTheme(
            colorScheme = colorScheme,
            typography = tipografiaEcolecta(familiaInter()),
            shapes = EcolectaShapes,
            content = content,
        )
    }
}
