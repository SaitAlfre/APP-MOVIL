package pe.ecolecta.presentation.admin.usuarios

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.outlined.AdminPanelSettings
import androidx.compose.material.icons.outlined.LocalShipping
import androidx.compose.material.icons.outlined.Science
import androidx.compose.material.icons.outlined.Storefront
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.presentation.admin.design.AdminColor
import pe.ecolecta.presentation.admin.design.AdminEtiqueta
import pe.ecolecta.presentation.admin.design.AdminTexto

/** Color de cada perfil, igual que en el resto de la app (verde admin/acopio, morado calidad, azul proveedor). */
fun Rol.color(): Color = when (this) {
    Rol.ADMIN -> AdminColor.verdeOscuro
    Rol.ACOPIADOR -> AdminColor.verde
    Rol.CALIDAD -> AdminColor.morado
    Rol.PROVEEDOR -> AdminColor.azul
}

fun Rol.fondo(): Color = when (this) {
    Rol.ADMIN, Rol.ACOPIADOR -> AdminColor.verdeSuave
    Rol.CALIDAD -> AdminColor.moradoSuave
    Rol.PROVEEDOR -> AdminColor.azulSuave
}

fun Rol.icono(): ImageVector = when (this) {
    Rol.ADMIN -> Icons.Outlined.AdminPanelSettings
    Rol.ACOPIADOR -> Icons.Outlined.LocalShipping
    Rol.CALIDAD -> Icons.Outlined.Science
    Rol.PROVEEDOR -> Icons.Outlined.Storefront
}

@Composable
fun EtiquetaRol(rol: Rol) = AdminEtiqueta(rol.etiqueta, rol.color(), rol.fondo())

/** Iniciales sobre el color del rol principal de la cuenta. */
@Composable
fun AvatarCuenta(nombres: String, roles: List<Rol>, activo: Boolean) {
    val rol = Rol.entries.firstOrNull { it in roles } ?: Rol.ACOPIADOR
    val iniciales = nombres.split(" ").filter { it.isNotBlank() }.take(2).joinToString("") { it.first().uppercase() }
    Box(
        Modifier.size(44.dp).clip(CircleShape).background(if (activo) rol.fondo() else AdminColor.grisSuave),
        contentAlignment = Alignment.Center,
    ) { AdminTexto(iniciales.ifBlank { "?" }, 15, if (activo) rol.color() else AdminColor.gris, FontWeight.Bold) }
}
