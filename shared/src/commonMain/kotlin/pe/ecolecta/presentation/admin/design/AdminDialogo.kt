package pe.ecolecta.presentation.admin.design

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/**
 * Diálogo del Administrador en claro, como el resto del módulo: el AlertDialog por defecto toma la
 * superficie oscura del tema de la app y dejaba textos grises casi ilegibles. Mientras [procesando]
 * no se puede confirmar otra vez ni cerrar, así una acción no se envía dos veces.
 */
@Composable
fun AdminDialogo(
    titulo: String,
    textoConfirmar: String,
    onConfirmar: () -> Unit,
    onCancelar: () -> Unit,
    habilitado: Boolean = true,
    procesando: Boolean = false,
    colorConfirmar: androidx.compose.ui.graphics.Color = AdminColor.verdeOscuro,
    contenido: @Composable () -> Unit,
) {
    AlertDialog(
        onDismissRequest = { if (!procesando) onCancelar() },
        shape = RoundedCornerShape(22.dp),
        containerColor = AdminColor.blanco,
        titleContentColor = AdminColor.texto,
        textContentColor = AdminColor.texto,
        title = { AdminTexto(titulo, 18, peso = FontWeight.SemiBold) },
        text = { Column(Modifier.verticalScroll(rememberScrollState()), verticalArrangement = Arrangement.spacedBy(10.dp)) { contenido() } },
        confirmButton = {
            TextButton(onClick = onConfirmar, enabled = habilitado && !procesando) {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    if (procesando) CircularProgressIndicator(Modifier.size(16.dp), color = colorConfirmar, strokeWidth = 2.dp)
                    Text(
                        if (procesando) "Guardando…" else textoConfirmar,
                        color = if (habilitado && !procesando) colorConfirmar else AdminColor.gris,
                        fontWeight = FontWeight.SemiBold, fontSize = 14.sp,
                    )
                }
            }
        },
        dismissButton = {
            TextButton(onClick = onCancelar, enabled = !procesando) { Text("Cancelar", color = AdminColor.gris, fontSize = 14.sp) }
        },
    )
}

/** Diálogo que exige motivo (anular, rechazar, resolver…); [error] se muestra dentro para no perder lo escrito. */
@Composable
fun AdminDialogoMotivo(
    titulo: String,
    explicacion: String,
    textoConfirmar: String,
    onConfirmar: (String) -> Unit,
    onCancelar: () -> Unit,
    procesando: Boolean = false,
    error: String? = null,
    colorConfirmar: androidx.compose.ui.graphics.Color = AdminColor.verdeOscuro,
    extra: (@Composable () -> Unit)? = null,
) {
    var motivo by rememberSaveable { mutableStateOf("") }
    AdminDialogo(
        titulo = titulo,
        textoConfirmar = textoConfirmar,
        onConfirmar = { onConfirmar(motivo.trim()) },
        onCancelar = onCancelar,
        habilitado = motivo.isNotBlank(),
        procesando = procesando,
        colorConfirmar = colorConfirmar,
    ) {
        AdminTexto(explicacion, 13, AdminColor.gris)
        extra?.invoke()
        AdminCampo(motivo, { motivo = it.take(300) }, "Motivo (obligatorio)", marcador = "Explica por qué")
        error?.let { AdminTexto(it, 12, AdminColor.rojo, FontWeight.Medium) }
    }
}
