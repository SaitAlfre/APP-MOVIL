package pe.ecolecta.presentation.design

import androidx.compose.runtime.Composable
import androidx.compose.runtime.ReadOnlyComposable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color

/**
 * Paleta semántica de Ecolecta Huata, alineada 1:1 con los tokens `--eh-*` del panel web
 * (web/resources/css/app.css, diseño "Lumen"): tinta #142820 como color de marca, lima #d8ff57
 * como acento, fondo crema #f4f4ef, arena y salvia. En oscuro, como en la web, la acción
 * principal pasa a lima con texto tinta.
 */
data class PaletaEcolecta(
    val bgBase: Color,
    val surface: Color,
    val surfaceAlta: Color,
    val brand: Color,
    val onBrand: Color,
    val brandText: Color,
    val brandContainer: Color,
    val onBrandContainer: Color,
    val secundario: Color,
    val onSecundario: Color,
    val textPrimary: Color,
    val textSecundario: Color,
    val borde: Color,
    val bordeFuerte: Color,
    val peligro: Color,
    val onPeligro: Color,
    val advertencia: Color,
    val exito: Color,
    val info: Color,
    val lima: Color,
    val tinta: Color,
    val salvia: Color,
    val arena: Color,
    val violeta: Color,
    val coral: Color,
    val exitoSuave: Color,
    val peligroSuave: Color,
    val advertenciaSuave: Color,
    val infoSuave: Color,
    val violetaSuave: Color,
)

val PaletaClara = PaletaEcolecta(
    bgBase = Color(0xFFF4F4EF),
    surface = Color(0xFFFFFFFF),
    surfaceAlta = Color(0xFFEEF0E9),
    brand = Color(0xFF142820),
    onBrand = Color(0xFFFFFFFF),
    brandText = Color(0xFF39765D),
    brandContainer = Color(0xFFE3EBE3),
    onBrandContainer = Color(0xFF142820),
    secundario = Color(0xFF926B22),
    onSecundario = Color(0xFFFFFFFF),
    textPrimary = Color(0xFF17231E),
    textSecundario = Color(0xFF66706B),
    borde = Color(0x1417231E),
    bordeFuerte = Color(0x2617231E),
    peligro = Color(0xFFB55E4D),
    onPeligro = Color(0xFFFFFFFF),
    advertencia = Color(0xFF926B22),
    exito = Color(0xFF39765D),
    info = Color(0xFF285989),
    lima = Color(0xFFD8FF57),
    tinta = Color(0xFF142820),
    salvia = Color(0xFF6C8C7B),
    arena = Color(0xFFE6DDD1),
    violeta = Color(0xFF655497),
    coral = Color(0xFFEF765D),
    exitoSuave = Color(0xFFE3F1E8),
    peligroSuave = Color(0xFFF8E5DF),
    advertenciaSuave = Color(0xFFFFF0D1),
    infoSuave = Color(0xFFD9ECFF),
    violetaSuave = Color(0xFFEDE9F8),
)

val PaletaOscura = PaletaEcolecta(
    bgBase = Color(0xFF0F1A15),
    surface = Color(0xFF16241E),
    surfaceAlta = Color(0xFF1D2C25),
    brand = Color(0xFFD8FF57),
    onBrand = Color(0xFF142820),
    brandText = Color(0xFF8FCAA9),
    brandContainer = Color(0xFF22342B),
    onBrandContainer = Color(0xFFEEF1EA),
    secundario = Color(0xFFE5BD6A),
    onSecundario = Color(0xFF2A2107),
    textPrimary = Color(0xFFEEF1EA),
    textSecundario = Color(0xFF9AA7A0),
    borde = Color(0x14FFFFFF),
    bordeFuerte = Color(0x24FFFFFF),
    peligro = Color(0xFFEC8F7C),
    onPeligro = Color(0xFF3A221D),
    advertencia = Color(0xFFE5BD6A),
    exito = Color(0xFF8FCAA9),
    info = Color(0xFF8CBFEE),
    lima = Color(0xFFD8FF57),
    tinta = Color(0xFF0A1410),
    salvia = Color(0xFF7BA18E),
    arena = Color(0xFF2A2721),
    violeta = Color(0xFFB3A6E0),
    coral = Color(0xFFEF765D),
    exitoSuave = Color(0xFF1F3329),
    peligroSuave = Color(0xFF3A221D),
    advertenciaSuave = Color(0xFF332A17),
    infoSuave = Color(0xFF1C2B3A),
    violetaSuave = Color(0xFF28233A),
)

val LocalPaletaEcolecta = staticCompositionLocalOf { PaletaClara }

/**
 * Fachada estable: expone la paleta activa (según [EcolectaTheme]) con los mismos nombres que
 * usan las pantallas existentes, para tematizar claro/oscuro sin tocar cada call-site.
 */
object Colores {
    val bgBase: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.bgBase
    val surface: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.surface
    val surfaceAlta: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.surfaceAlta
    val brand: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.brand
    val onBrand: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.onBrand
    val brandText: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.brandText
    val brandContainer: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.brandContainer
    val onBrandContainer: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.onBrandContainer
    val secundario: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.secundario
    val onSecundario: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.onSecundario
    val textPrimary: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.textPrimary
    val textSecundario: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.textSecundario
    val borde: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.borde
    val bordeFuerte: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.bordeFuerte
    val peligro: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.peligro
    val onPeligro: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.onPeligro
    val advertencia: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.advertencia
    val exito: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.exito
    val info: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.info
    val lima: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.lima
    val tinta: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.tinta
    val salvia: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.salvia
    val arena: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.arena
    val violeta: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.violeta
    val coral: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.coral
    val exitoSuave: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.exitoSuave
    val peligroSuave: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.peligroSuave
    val advertenciaSuave: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.advertenciaSuave
    val infoSuave: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.infoSuave
    val violetaSuave: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.violetaSuave
}
