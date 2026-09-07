package pe.ecolecta.presentation.design

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Shapes
import androidx.compose.material3.Typography
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Formas redondeadas y modernas, consistentes para cards, botones, campos y diálogos en toda la app. */
val EcolectaShapes = Shapes(
    extraSmall = RoundedCornerShape(8.dp),
    small = RoundedCornerShape(12.dp),
    medium = RoundedCornerShape(16.dp),
    large = RoundedCornerShape(20.dp),
    extraLarge = RoundedCornerShape(28.dp),
)

/** Escala tipográfica única y limitada (pocos tamaños/pesos) para una jerarquía clara y predecible. */
val EcolectaTypography = Typography(
    displaySmall = TextStyle(fontSize = 34.sp, fontWeight = FontWeight.Bold, lineHeight = 40.sp),
    headlineLarge = TextStyle(fontSize = 28.sp, fontWeight = FontWeight.Bold, lineHeight = 34.sp),
    headlineMedium = TextStyle(fontSize = 24.sp, fontWeight = FontWeight.Bold, lineHeight = 30.sp),
    headlineSmall = TextStyle(fontSize = 20.sp, fontWeight = FontWeight.SemiBold, lineHeight = 26.sp),
    titleLarge = TextStyle(fontSize = 20.sp, fontWeight = FontWeight.SemiBold, lineHeight = 26.sp),
    titleMedium = TextStyle(fontSize = 16.sp, fontWeight = FontWeight.SemiBold, lineHeight = 22.sp),
    titleSmall = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.SemiBold, lineHeight = 20.sp),
    bodyLarge = TextStyle(fontSize = 16.sp, fontWeight = FontWeight.Normal, lineHeight = 24.sp),
    bodyMedium = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.Normal, lineHeight = 20.sp),
    bodySmall = TextStyle(fontSize = 12.sp, fontWeight = FontWeight.Normal, lineHeight = 18.sp),
    labelLarge = TextStyle(fontSize = 14.sp, fontWeight = FontWeight.Medium, lineHeight = 20.sp),
    labelMedium = TextStyle(fontSize = 12.sp, fontWeight = FontWeight.Medium, lineHeight = 16.sp),
    labelSmall = TextStyle(fontSize = 11.sp, fontWeight = FontWeight.Medium, lineHeight = 14.sp),
)

private fun esquemaClaro(paleta: PaletaEcolecta) = lightColorScheme(
    primary = paleta.brand,
    onPrimary = paleta.onBrand,
    primaryContainer = paleta.brandContainer,
    onPrimaryContainer = paleta.onBrandContainer,
    secondary = paleta.secundario,
    onSecondary = paleta.onSecundario,
    secondaryContainer = paleta.secundario.copy(alpha = 0.16f),
    onSecondaryContainer = paleta.textPrimary,
    tertiary = paleta.info,
    onTertiary = paleta.onBrand,
    background = paleta.bgBase,
    onBackground = paleta.textPrimary,
    surface = paleta.surface,
    onSurface = paleta.textPrimary,
    surfaceVariant = paleta.surfaceAlta,
    onSurfaceVariant = paleta.textSecundario,
    surfaceContainer = paleta.surfaceAlta,
    surfaceContainerHigh = paleta.surfaceAlta,
    surfaceContainerLow = paleta.bgBase,
    outline = paleta.borde,
    outlineVariant = paleta.borde,
    error = paleta.peligro,
    onError = paleta.onPeligro,
    errorContainer = paleta.peligro.copy(alpha = 0.14f),
    onErrorContainer = paleta.peligro,
)

private fun esquemaOscuro(paleta: PaletaEcolecta) = darkColorScheme(
    primary = paleta.brand,
    onPrimary = paleta.onBrand,
    primaryContainer = paleta.brandContainer,
    onPrimaryContainer = paleta.onBrandContainer,
    secondary = paleta.secundario,
    onSecondary = paleta.onSecundario,
    secondaryContainer = paleta.secundario.copy(alpha = 0.22f),
    onSecondaryContainer = paleta.textPrimary,
    tertiary = paleta.info,
    onTertiary = paleta.onBrand,
    background = paleta.bgBase,
    onBackground = paleta.textPrimary,
    surface = paleta.surface,
    onSurface = paleta.textPrimary,
    surfaceVariant = paleta.surfaceAlta,
    onSurfaceVariant = paleta.textSecundario,
    surfaceContainer = paleta.surfaceAlta,
    surfaceContainerHigh = paleta.surfaceAlta,
    surfaceContainerLow = paleta.bgBase,
    outline = paleta.borde,
    outlineVariant = paleta.borde,
    error = paleta.peligro,
    onError = paleta.onPeligro,
    errorContainer = paleta.peligro.copy(alpha = 0.20f),
    onErrorContainer = paleta.peligro,
)

/**
 * Punto de entrada único del Design System de Ecolecta Huata. Envuelve [MaterialTheme] con una
 * paleta clara u oscura (según el sistema), tipografía y formas compartidas por ADMIN, ACOPIADOR
 * y PROVEEDOR, y expone la paleta activa a [Colores] vía [LocalPaletaEcolecta].
 */
@Composable
fun EcolectaTheme(oscuroForzado: Boolean? = null, content: @Composable () -> Unit) {
    val esOscuro = oscuroForzado ?: isSystemInDarkTheme()
    val paleta = if (esOscuro) PaletaOscura else PaletaClara
    val colorScheme = if (esOscuro) esquemaOscuro(paleta) else esquemaClaro(paleta)

    CompositionLocalProvider(LocalPaletaEcolecta provides paleta) {
        MaterialTheme(
            colorScheme = colorScheme,
            typography = EcolectaTypography,
            shapes = EcolectaShapes,
            content = content,
        )
    }
}
