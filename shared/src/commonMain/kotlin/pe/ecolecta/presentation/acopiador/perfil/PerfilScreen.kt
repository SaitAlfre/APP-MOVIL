package pe.ecolecta.presentation.acopiador.perfil

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta

@Composable
fun PerfilScreen(viewModel: PerfilViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(horizontal = Espaciado.l),
        verticalArrangement = Arrangement.spacedBy(Espaciado.l),
    ) {
        EncabezadoSeccion("Perfil", modifier = Modifier.padding(horizontal = 0.dp))

        Tarjeta {
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(Espaciado.m)) {
                Box(
                    Modifier.size(48.dp).clip(CircleShape).background(Colores.brandContainer),
                    contentAlignment = Alignment.Center,
                ) {
                    Icon(Icons.Filled.Person, contentDescription = null, tint = Colores.onBrandContainer, modifier = Modifier.size(26.dp))
                }
                Column {
                    Text(estado.nombres, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                    Text("@${estado.username}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                }
            }
            Spacer(Modifier.height(Espaciado.s))
            ChipEstado(estado.rol, Colores.brand)
        }

        Tarjeta {
            Dato("Zona actual", estado.zonaActual, Icons.Filled.LocationOn)
            DivisorSutil(Modifier.padding(vertical = Espaciado.s))
            Dato("Vehículo actual", estado.vehiculoActual, Icons.Filled.LocalShipping)
            DivisorSutil(Modifier.padding(vertical = Espaciado.s))
            Dato("Pendientes por sincronizar", estado.pendientesSync.toString(), Icons.Filled.CloudUpload)
        }

        OutlinedButton(
            onClick = viewModel::solicitarCierreSesion,
            modifier = Modifier.fillMaxWidth().height(52.dp),
            shape = MaterialTheme.shapes.medium,
            colors = ButtonDefaults.outlinedButtonColors(contentColor = Colores.peligro),
        ) {
            Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null, modifier = Modifier.size(20.dp))
            Spacer(Modifier.width(Espaciado.xs))
            Text("Cerrar sesión")
        }

        Spacer(Modifier.height(Espaciado.l))
    }

    if (estado.mostrarConfirmacionCierre) {
        AlertDialog(
            onDismissRequest = viewModel::cancelarCierreSesion,
            shape = MaterialTheme.shapes.large,
            title = { Text("Tienes registros pendientes") },
            text = { Text("Tienes ${estado.pendientesSync} registros pendientes de sincronizar. ¿Deseas cerrar sesión de todas formas? Tus datos no se perderán.") },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreSesion) { Text("Cerrar sesión", color = Colores.peligro) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreSesion) { Text("Cancelar") } },
        )
    }
}

@Composable
private fun Dato(etiqueta: String, valor: String, icono: ImageVector) {
    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
        Icon(icono, contentDescription = null, tint = Colores.textSecundario, modifier = Modifier.size(20.dp))
        Column {
            Text(etiqueta, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
            Text(valor, style = MaterialTheme.typography.bodyLarge, color = Colores.textPrimary)
        }
    }
}
