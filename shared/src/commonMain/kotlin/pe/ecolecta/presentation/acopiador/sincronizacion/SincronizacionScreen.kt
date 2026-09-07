package pe.ecolecta.presentation.acopiador.sincronizacion

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.CloudDone
import androidx.compose.material.icons.filled.CloudUpload
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun SincronizacionScreen(viewModel: SincronizacionViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()
    val resumen = estado.resumen
    val todoSincronizado = resumen.pendientes == 0 && resumen.errores == 0 && resumen.conflictos == 0

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(horizontal = Espaciado.l)) {
        EncabezadoSeccion("Cola de sincronización", modifier = Modifier.padding(horizontal = 0.dp))

        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            TarjetaEstadistica("Pendientes", resumen.pendientes.toString(), icono = Icons.Filled.CloudUpload, color = Colores.info, modifier = Modifier.weight(1f))
            TarjetaEstadistica("Sincronizados", resumen.sincronizados.toString(), icono = Icons.Filled.CheckCircle, color = Colores.exito, modifier = Modifier.weight(1f))
        }
        Spacer(Modifier.height(Espaciado.s))
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            TarjetaEstadistica("Errores", resumen.errores.toString(), icono = Icons.Filled.ErrorOutline, color = Colores.peligro, modifier = Modifier.weight(1f))
            TarjetaEstadistica("Conflictos", resumen.conflictos.toString(), icono = Icons.Filled.WarningAmber, color = Colores.advertencia, modifier = Modifier.weight(1f))
        }

        Spacer(Modifier.height(Espaciado.l))

        if (todoSincronizado) {
            EstadoVacio(
                titulo = "Todo sincronizado",
                descripcion = "No tienes registros pendientes de enviar.",
                icono = Icons.Filled.CloudDone,
            )
        }

        estado.mensaje?.let {
            Banner(it, TipoBanner.INFO)
            Spacer(Modifier.height(Espaciado.m))
        }

        BotonPrimario("Reintentar", viewModel::reintentar, icono = Icons.Filled.Sync)

        Spacer(Modifier.height(Espaciado.l))
    }
}
