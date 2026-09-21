package pe.ecolecta.presentation.admin.zonas

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Place
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
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta

@Composable
fun ZonaFormScreen(
    id: String?,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    viewModel: ZonaFormViewModel = koinViewModel(key = id ?: "nuevo", parameters = { parametersOf(id) }),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.guardadoExitoso) { if (estado.guardadoExitoso) alGuardar() }

    Column(Modifier.fillMaxSize()) {
        BarraSuperior(if (estado.esEdicion) "Editar zona" else "Nueva zona", alVolver = alVolver)
        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            Tarjeta {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    CampoTexto(
                        estado.nombre,
                        { viewModel.onEvent(ZonaFormUiEvent.NombreCambia(it)) },
                        "Nombre",
                        error = estado.error,
                        iconoInicial = Icons.Filled.Place,
                    )
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Text("Activa", color = Colores.textPrimary)
                        Switch(checked = estado.activo, onCheckedChange = { viewModel.onEvent(ZonaFormUiEvent.ActivoCambia(it)) })
                    }
                }
            }
            BotonPrimario("Guardar", { viewModel.onEvent(ZonaFormUiEvent.Guardar) }, habilitado = estado.puedeGuardar && !estado.cargando)
        }
    }
}
