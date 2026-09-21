package pe.ecolecta.presentation.auth

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AdminPanelSettings
import androidx.compose.material.icons.filled.Factory
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material.icons.filled.Outbox
import androidx.compose.material.icons.filled.Science
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material.icons.filled.SupportAgent
import androidx.compose.material.icons.filled.SwitchAccount
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun SeleccionRolScreen(
    usuarioId: String,
    alRolSeleccionado: (Rol) -> Unit,
    viewModel: SeleccionRolViewModel = koinViewModel(parameters = { parametersOf(usuarioId) }),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.rolSeleccionado) {
        estado.rolSeleccionado?.let(alRolSeleccionado)
    }

    Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
        Column(
            modifier = Modifier.widthIn(max = 400.dp).fillMaxWidth().padding(Espaciado.xl),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(Espaciado.xl),
        ) {
            Box(
                Modifier.size(56.dp),
                contentAlignment = Alignment.Center,
            ) {
                Icon(Icons.Filled.SwitchAccount, contentDescription = null, tint = Colores.brandText, modifier = Modifier.size(40.dp))
            }
            Text("¿Con qué rol quieres ingresar?", style = MaterialTheme.typography.headlineSmall, color = Colores.textPrimary)

            estado.error?.let { Banner(it, TipoBanner.ERROR) }

            Column(Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                estado.roles.forEach { rol ->
                    TarjetaRol(
                        rol = rol,
                        habilitado = !estado.seleccionando,
                        onClick = { viewModel.seleccionar(rol) },
                    )
                }
            }
        }
    }
}

@Composable
private fun TarjetaRol(rol: Rol, habilitado: Boolean, onClick: () -> Unit) {
    Tarjeta(onClick = if (habilitado) onClick else null) {
        androidx.compose.foundation.layout.Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Espaciado.m),
        ) {
            Icon(iconoDeRol(rol), contentDescription = null, tint = Colores.brandText)
            Text(etiquetaDeRol(rol), style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
        }
    }
}

private fun iconoDeRol(rol: Rol): ImageVector = when (rol) {
    Rol.ADMIN -> Icons.Filled.AdminPanelSettings
    Rol.ACOPIADOR -> Icons.Filled.LocalShipping
    Rol.PROVEEDOR -> Icons.Filled.Storefront
    Rol.CALIDAD -> Icons.Filled.Science
    Rol.ASISTENTE -> Icons.Filled.SupportAgent
    Rol.PRODUCCION -> Icons.Filled.Factory
    Rol.DESPACHO -> Icons.Filled.Outbox
}

private fun etiquetaDeRol(rol: Rol): String = when (rol) {
    Rol.ADMIN -> "Administrador"
    Rol.ACOPIADOR -> "Acopiador"
    Rol.PROVEEDOR -> "Proveedor"
    Rol.CALIDAD -> "Calidad"
    Rol.ASISTENTE -> "Asistente"
    Rol.PRODUCCION -> "Producción"
    Rol.DESPACHO -> "Despacho"
}
