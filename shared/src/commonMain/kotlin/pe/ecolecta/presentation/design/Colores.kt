package pe.ecolecta.presentation.design

import androidx.compose.runtime.Composable
import androidx.compose.runtime.ReadOnlyComposable
import androidx.compose.runtime.staticCompositionLocalOf
import androidx.compose.ui.graphics.Color

/**
 * Paleta semántica de la identidad visual de Ecolecta Huata, compartida por ADMIN, ACOPIADOR y
 * PROVEEDOR. Un verde "pastizal" como color de marca (recolección/agro), acentos cálidos color
 * "crema/manteca" para lo secundario y un azul suave para estados informativos/sincronización.
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
    val peligro: Color,
    val onPeligro: Color,
    val advertencia: Color,
    val exito: Color,
    val info: Color,
)

val PaletaClara = PaletaEcolecta(
    bgBase = Color(0xFFF7F6F1),
    surface = Color(0xFFFFFFFF),
    surfaceAlta = Color(0xFFEFEDE4),
    brand = Color(0xFF2E7D46),
    onBrand = Color(0xFFFFFFFF),
    brandText = Color(0xFF1E6B37),
    brandContainer = Color(0xFFD9F2DD),
    onBrandContainer = Color(0xFF0B3D1E),
    secundario = Color(0xFF9C6B1F),
    onSecundario = Color(0xFFFFFFFF),
    textPrimary = Color(0xFF1B1F1A),
    textSecundario = Color(0xFF5C6259),
    borde = Color(0xFFDEDCD2),
    peligro = Color(0xFFBA2E22),
    onPeligro = Color(0xFFFFFFFF),
    advertencia = Color(0xFFA8690A),
    exito = Color(0xFF2E7D46),
    info = Color(0xFF386FA4),
)

val PaletaOscura = PaletaEcolecta(
    bgBase = Color(0xFF11170F),
    surface = Color(0xFF1B2418),
    surfaceAlta = Color(0xFF243020),
    brand = Color(0xFF7FD98A),
    onBrand = Color(0xFF0B3D1E),
    brandText = Color(0xFF8BE28F),
    brandContainer = Color(0xFF1F4A2C),
    onBrandContainer = Color(0xFFB9F5C0),
    secundario = Color(0xFFD9B26A),
    onSecundario = Color(0xFF3E2C05),
    textPrimary = Color(0xFFEFF3ED),
    textSecundario = Color(0xFFAAB6A4),
    borde = Color(0xFF33402E),
    peligro = Color(0xFFE0857A),
    onPeligro = Color(0xFF3E0B06),
    advertencia = Color(0xFFE0A93C),
    exito = Color(0xFF6FCB8B),
    info = Color(0xFF7FADDE),
)

val LocalPaletaEcolecta = staticCompositionLocalOf { PaletaClara }

/**
 * Fachada estable: expone la paleta activa (según [EcolectaTheme]) con los mismos nombres que
 * usan las ~30 pantallas existentes, para tematizar claro/oscuro sin tocar cada call-site.
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
    val peligro: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.peligro
    val onPeligro: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.onPeligro
    val advertencia: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.advertencia
    val exito: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.exito
    val info: Color @Composable @ReadOnlyComposable get() = LocalPaletaEcolecta.current.info
}
