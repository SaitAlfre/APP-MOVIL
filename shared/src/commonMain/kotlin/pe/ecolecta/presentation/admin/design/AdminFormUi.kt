package pe.ecolecta.presentation.admin.design

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Chip de filtro con contador opcional (Alertas, Usuarios, Jornadas…). */
@Composable
fun AdminChip(texto: String, activo: Boolean, color: Color = AdminColor.verdeOscuro, cantidad: Int? = null, onClick: () -> Unit) {
    Row(
        Modifier.clip(RoundedCornerShape(20.dp)).background(if (activo) color else AdminColor.blanco)
            .clickable(onClick = onClick).padding(horizontal = 14.dp, vertical = 8.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        AdminTexto(texto, 13, if (activo) AdminColor.blanco else AdminColor.texto, FontWeight.SemiBold, maxLineas = 1)
        cantidad?.let { AdminTexto(it.toString(), 12, if (activo) AdminColor.blanco.copy(alpha = 0.8f) else AdminColor.gris, FontWeight.Bold) }
    }
}

@Composable
private fun coloresCampo(fondo: Color) = OutlinedTextFieldDefaults.colors(
    unfocusedContainerColor = fondo, focusedContainerColor = AdminColor.blanco,
    disabledContainerColor = AdminColor.grisSuave, disabledTextColor = AdminColor.gris,
    unfocusedBorderColor = AdminColor.borde, focusedBorderColor = AdminColor.verde, disabledBorderColor = AdminColor.borde,
    focusedTextColor = AdminColor.texto, unfocusedTextColor = AdminColor.texto,
    focusedLeadingIconColor = AdminColor.verde, unfocusedLeadingIconColor = AdminColor.gris,
)

/** Campo de texto del prototipo (.input-field): crema, borde suave y verde al enfocar. */
@Composable
fun AdminCampo(
    valor: String,
    onCambio: (String) -> Unit,
    etiqueta: String,
    modifier: Modifier = Modifier,
    marcador: String? = null,
    soloLectura: Boolean = false,
    teclado: KeyboardType = KeyboardType.Text,
    esPin: Boolean = false,
    ayuda: String? = null,
    icono: ImageVector? = null,
) {
    Column(modifier.fillMaxWidth()) {
        AdminTexto(etiqueta, 13, AdminColor.texto, FontWeight.SemiBold, modifier = Modifier.padding(bottom = 6.dp))
        OutlinedTextField(
            value = valor,
            onValueChange = onCambio,
            modifier = Modifier.fillMaxWidth(),
            enabled = !soloLectura,
            singleLine = true,
            placeholder = marcador?.let { { Text(it, color = AdminColor.gris) } },
            leadingIcon = icono?.let { { Icon(it, contentDescription = null) } },
            shape = RoundedCornerShape(12.dp),
            keyboardOptions = KeyboardOptions(keyboardType = teclado),
            visualTransformation = if (esPin) PasswordVisualTransformation() else VisualTransformation.None,
            colors = coloresCampo(AdminColor.crema),
        )
        ayuda?.let { AdminTexto(it, 11, AdminColor.gris, modifier = Modifier.padding(top = 4.dp)) }
    }
}

@Composable
fun AdminBuscador(valor: String, onCambio: (String) -> Unit, marcador: String, modifier: Modifier = Modifier) {
    OutlinedTextField(
        value = valor,
        onValueChange = onCambio,
        modifier = modifier.fillMaxWidth(),
        singleLine = true,
        placeholder = { Text(marcador, color = AdminColor.gris, fontSize = 14.sp) },
        leadingIcon = { Icon(Icons.Filled.Search, contentDescription = null) },
        shape = RoundedCornerShape(12.dp),
        colors = coloresCampo(AdminColor.blanco),
    )
}

/** Fila título → interruptor, para "Cuenta activa", "Restablecer PIN", etc. */
@Composable
fun AdminInterruptor(titulo: String, detalle: String?, valor: Boolean, onCambio: (Boolean) -> Unit, habilitado: Boolean = true) {
    Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
        Column(Modifier.weight(1f).padding(end = 8.dp)) {
            AdminTexto(titulo, 14, peso = FontWeight.SemiBold)
            detalle?.let { AdminTexto(it, 12, AdminColor.gris) }
        }
        Switch(
            checked = valor, onCheckedChange = onCambio, enabled = habilitado,
            colors = SwitchDefaults.colors(checkedTrackColor = AdminColor.verde, checkedThumbColor = AdminColor.blanco),
        )
    }
}

/** Opción seleccionable en tarjeta (roles, zonas, fichas…). */
@Composable
fun AdminOpcion(
    titulo: String,
    detalle: String?,
    seleccionada: Boolean,
    onClick: () -> Unit,
    color: Color = AdminColor.verde,
    icono: ImageVector? = null,
    habilitada: Boolean = true,
) {
    Row(
        Modifier.fillMaxWidth().clip(RoundedCornerShape(12.dp))
            .background(if (seleccionada) color.copy(alpha = 0.08f) else AdminColor.blanco)
            .border(1.5.dp, if (seleccionada) color else AdminColor.borde, RoundedCornerShape(12.dp))
            .clickable(enabled = habilitada, onClick = onClick).padding(12.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        icono?.let { Icon(it, contentDescription = null, tint = if (habilitada) color else AdminColor.borde, modifier = Modifier.size(24.dp)) }
        Column(Modifier.weight(1f)) {
            AdminTexto(titulo, 14, if (habilitada) AdminColor.texto else AdminColor.gris, FontWeight.Bold)
            detalle?.let { AdminTexto(it, 12, AdminColor.gris) }
        }
        Box(
            Modifier.size(22.dp).clip(CircleShape).background(if (seleccionada) color else AdminColor.blanco)
                .border(1.5.dp, if (seleccionada) color else AdminColor.borde, CircleShape),
            contentAlignment = Alignment.Center,
        ) { if (seleccionada) AdminTexto("✓", 12, AdminColor.blanco, FontWeight.Bold) }
    }
}
