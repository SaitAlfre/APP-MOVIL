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
import androidx.compose.material.icons.filled.EventBusy
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.LocationOn
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Dato
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun PerfilScreen(viewModel: PerfilViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    // El ViewModel vive toda la sesión (no se recrea al cambiar de pestaña): sin esto, "pendientes
    // por sincronizar" se congela en el valor del primer ingreso a esta pantalla.
    LaunchedEffect(Unit) { viewModel.cargar() }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Perfil")

        Column(
            Modifier.padding(horizontal = Espaciado.l),
            verticalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Tarjeta {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(Espaciado.m)) {
                    Box(
                        Modifier.size(48.dp).clip(CircleShape).background(Colores.brandContainer),
                        contentAlignment = Alignment.Center,
                    ) {
                        Icon(Icons.Filled.Person, contentDescription = null, tint = Colores.onBrandContainer, modifier = Modifier.size(26.dp))
                    }
                    Column {
                        Text(estado.nombres, style = MaterialTheme.typography.titleLarge, color = Colores.textPrimary)
                        Text("@${estado.username}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                        Spacer(Modifier.height(Espaciado.xxs))
                        ChipEstado(estado.rol, Colores.brand, mostrarPunto = false)
                    }
                }
            }

            TituloSeccion("Jornada actual")
            Tarjeta {
                Dato("Zona actual", estado.zonaActual, icono = Icons.Filled.LocationOn)
                Dato("Vehículo actual", estado.vehiculoActual, icono = Icons.Filled.LocalShipping, ultimo = true)
            }

            TituloSeccion("Vinculación remota")
            Tarjeta {
                Dato("ID de acopiador (local)", estado.usuarioIdLocal)
                DivisorSutil(Modifier.padding(bottom = Espaciado.s))
                Dato("ID de dispositivo (Firebase)", estado.uidFirebase ?: "No disponible en esta plataforma", ultimo = true)
            }

            if (estado.jornadaAbierta) {
                BotonBorde(
                    texto = if (estado.cerrandoJornada) "Cerrando jornada…" else "Cerrar jornada",
                    color = Colores.advertencia,
                    icono = Icons.Filled.EventBusy,
                    habilitado = !estado.cerrandoJornada,
                    cargando = estado.cerrandoJornada,
                    onClick = viewModel::solicitarCierreJornada,
                )
                estado.errorCierreJornada?.let { Banner(mensaje = it, tipo = TipoBanner.ERROR) }
            }

            BotonBorde(
                texto = "Cerrar sesión",
                color = Colores.peligro,
                icono = Icons.AutoMirrored.Filled.Logout,
                onClick = viewModel::solicitarCierreSesion,
            )

            Spacer(Modifier.height(Espaciado.l))
        }
    }

    if (estado.mostrarConfirmacionCierreJornada) {
        AlertDialog(
            onDismissRequest = viewModel::cancelarCierreJornada,
            shape = MaterialTheme.shapes.large,
            title = { Text("¿Cerrar la jornada?") },
            text = {
                Text(
                    "Se finalizará tu jornada de hoy y se detendrá el seguimiento de ubicación. " +
                        "Tus entregas y los pendientes por sincronizar no se pierden.",
                )
            },
            confirmButton = { TextButton(onClick = viewModel::confirmarCierreJornada) { Text("Cerrar jornada", color = Colores.advertencia) } },
            dismissButton = { TextButton(onClick = viewModel::cancelarCierreJornada) { Text("Cancelar") } },
        )
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
private fun TituloSeccion(texto: String) {
    Text(texto, style = MaterialTheme.typography.titleLarge, color = Colores.textPrimary)
}

/**
 * Botón de borde teñido para las dos salidas de la pantalla. Van delineados y no rellenos porque
 * ninguno es la acción que uno viene a hacer aquí: se pulsan al final del día, a propósito.
 */
@Composable
private fun BotonBorde(
    texto: String,
    color: Color,
    icono: ImageVector,
    onClick: () -> Unit,
    habilitado: Boolean = true,
    cargando: Boolean = false,
) {
    OutlinedButton(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth().height(52.dp),
        shape = MaterialTheme.shapes.medium,
        enabled = habilitado,
        colors = ButtonDefaults.outlinedButtonColors(contentColor = color),
    ) {
        if (cargando) {
            CircularProgressIndicator(modifier = Modifier.size(20.dp), color = color, strokeWidth = 2.dp)
        } else {
            Icon(icono, contentDescription = null, modifier = Modifier.size(20.dp))
        }
        Spacer(Modifier.width(Espaciado.xs))
        Text(texto, style = MaterialTheme.typography.titleMedium)
    }
}
