package pe.ecolecta.presentation.auth

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EcolectaLogo
import pe.ecolecta.presentation.design.Espaciado

@Composable
fun LoginScreen(
    alSesionIniciada: () -> Unit,
    alRequerirSeleccionRol: (usuarioId: String) -> Unit,
    viewModel: LoginViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.sesionIniciada) {
        if (estado.sesionIniciada) alSesionIniciada()
    }
    LaunchedEffect(estado.requiereSeleccionRol) {
        val usuarioId = estado.usuarioAutenticadoId
        if (estado.requiereSeleccionRol && usuarioId != null) alRequerirSeleccionRol(usuarioId)
    }

    Box(Modifier.fillMaxSize().background(Colores.bgBase), contentAlignment = Alignment.Center) {
        Column(
            modifier = Modifier.widthIn(max = 400.dp).fillMaxWidth().verticalScroll(rememberScrollState()).padding(Espaciado.xl),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(Espaciado.xxl),
        ) {
            Column(horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                EcolectaLogo(tamano = 72.dp)
                Text("Ecolecta Huata", style = MaterialTheme.typography.headlineMedium, color = Colores.textPrimary)
                Text(
                    "Gestión y recolección de leche",
                    style = MaterialTheme.typography.bodyMedium,
                    color = Colores.textSecundario,
                )
            }

            Column(Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
                CampoTexto(
                    valor = estado.username,
                    onValorCambia = { viewModel.onEvent(LoginUiEvent.UsernameCambia(it)) },
                    etiqueta = "Usuario",
                    iconoInicial = Icons.Filled.Badge,
                )
                CampoTexto(
                    valor = estado.pin,
                    onValorCambia = { if (it.length <= 4 && it.all(Char::isDigit)) viewModel.onEvent(LoginUiEvent.PinCambia(it)) },
                    etiqueta = "PIN (4 dígitos)",
                    iconoInicial = Icons.Filled.Lock,
                    error = estado.error,
                    esPin = true,
                )

                BotonPrimario(
                    texto = "Ingresar",
                    onClick = { viewModel.onEvent(LoginUiEvent.Ingresar) },
                    habilitado = estado.username.isNotBlank() && estado.pin.length == 4,
                    cargando = estado.cargando,
                )
            }
        }
    }
}
