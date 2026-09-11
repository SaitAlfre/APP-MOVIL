package pe.ecolecta.presentation.admin.usuarios

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.Checkbox
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun UsuarioFormScreen(
    id: String?,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    viewModel: UsuarioFormViewModel = koinViewModel(parameters = { parametersOf(id) }),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.guardadoExitoso) {
        if (estado.guardadoExitoso) alGuardar()
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        BarraSuperior(if (estado.esEdicion) "Editar usuario" else "Nuevo usuario", alVolver = alVolver)
        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            Tarjeta {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    CampoTexto(
                        valor = estado.username,
                        onValorCambia = { viewModel.onEvent(UsuarioFormUiEvent.UsernameCambia(it)) },
                        etiqueta = "Usuario",
                        soloLectura = estado.esEdicion,
                        iconoInicial = Icons.Filled.Person,
                    )
                    CampoTexto(
                        estado.nombres,
                        { viewModel.onEvent(UsuarioFormUiEvent.NombresCambia(it)) },
                        "Nombres",
                        iconoInicial = Icons.Filled.Person,
                    )
                    CampoTexto(
                        estado.dni,
                        { viewModel.onEvent(UsuarioFormUiEvent.DniCambia(it)) },
                        "DNI",
                        iconoInicial = Icons.Filled.Badge,
                    )
                }
            }

            Tarjeta {
                Text("Roles", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                Rol.entries.forEach { rol ->
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Checkbox(checked = rol in estado.roles, onCheckedChange = { viewModel.onEvent(UsuarioFormUiEvent.RolToggle(rol)) })
                        Text(rol.name, color = Colores.textPrimary)
                    }
                }
                Row(
                    Modifier.fillMaxWidth().padding(top = Espaciado.xs),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically,
                ) {
                    Text("Activo", color = Colores.textPrimary)
                    Switch(checked = estado.activo, onCheckedChange = { viewModel.onEvent(UsuarioFormUiEvent.ActivoCambia(it)) })
                }
                if (estado.esEdicion) {
                    Row(
                        Modifier.fillMaxWidth().padding(top = Espaciado.xs),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text("Cambiar PIN", color = Colores.textPrimary)
                        Switch(checked = estado.cambiarPin, onCheckedChange = { viewModel.onEvent(UsuarioFormUiEvent.CambiarPinToggle(it)) })
                    }
                }
            }

            if (!estado.esEdicion || estado.cambiarPin) {
                Tarjeta {
                    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                        CampoTexto(
                            estado.pin,
                            { if (it.length <= 4 && it.all(Char::isDigit)) viewModel.onEvent(UsuarioFormUiEvent.PinCambia(it)) },
                            "PIN (4 dígitos)",
                            esPin = true,
                        )
                        CampoTexto(
                            estado.confirmacionPin,
                            { if (it.length <= 4 && it.all(Char::isDigit)) viewModel.onEvent(UsuarioFormUiEvent.ConfirmacionPinCambia(it)) },
                            "Confirmar PIN",
                            error = estado.error,
                            esPin = true,
                        )
                    }
                }
            } else if (estado.error != null) {
                Banner(estado.error!!, TipoBanner.ERROR)
            }

            BotonPrimario(
                texto = "Guardar",
                onClick = { viewModel.onEvent(UsuarioFormUiEvent.Guardar) },
                habilitado = estado.puedeGuardar && !estado.cargando,
            )
        }
    }
}
