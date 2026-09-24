package pe.ecolecta.presentation.auth

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowForward
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Antetitulo
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EcolectaLogo
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.aparicionEscalonada

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

    // Misma composición que el login del panel web: banda tinta arriba y la tarjeta blanca flotando encima.
    Box(Modifier.fillMaxSize().background(Colores.bgBase)) {
        Box(Modifier.fillMaxWidth().fillMaxHeight(0.42f).background(Colores.tinta))
        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(horizontal = Espaciado.xl, vertical = Espaciado.xxxl),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.Center,
        ) {
            val forma = RoundedCornerShape(22.dp)
            Surface(
                modifier = Modifier
                    .widthIn(max = 400.dp)
                    .fillMaxWidth()
                    .aparicionEscalonada()
                    .shadow(40.dp, forma, clip = false, ambientColor = Color(0x2E142820), spotColor = Color(0x2E142820)),
                shape = forma,
                color = Colores.surface,
                border = BorderStroke(1.dp, Colores.borde),
            ) {
                Column(
                    Modifier.padding(horizontal = 28.dp, vertical = 32.dp),
                    verticalArrangement = Arrangement.spacedBy(Espaciado.xl),
                ) {
                    Column(Modifier.fillMaxWidth(), horizontalAlignment = Alignment.CenterHorizontally) {
                        EcolectaLogo(
                            tamano = 56.dp,
                            colorFondo = Colores.lima,
                            colorSimbolo = Color(0xFF142820),
                            forma = RoundedCornerShape(16.dp),
                            modifier = Modifier.shadow(12.dp, RoundedCornerShape(16.dp), clip = false, ambientColor = Colores.lima, spotColor = Colores.lima),
                        )
                        Text(
                            "Ecolecta",
                            style = MaterialTheme.typography.headlineMedium,
                            color = Colores.textPrimary,
                            modifier = Modifier.padding(top = Espaciado.m),
                        )
                        Antetitulo("Huata")
                        Text(
                            "Gestión y recolección de leche · Huata, Puno",
                            style = MaterialTheme.typography.bodySmall,
                            color = Colores.textSecundario,
                            textAlign = TextAlign.Center,
                            modifier = Modifier.padding(top = Espaciado.s),
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
                            icono = Icons.AutoMirrored.Filled.ArrowForward,
                        )
                    }

                    Text(
                        "Ecolecta Huata · Sistema de acopio y gestión láctea",
                        style = MaterialTheme.typography.bodySmall,
                        color = Colores.textSecundario,
                        textAlign = TextAlign.Center,
                        modifier = Modifier.fillMaxWidth(),
                    )
                }
            }
        }
    }
}
