package pe.ecolecta.presentation.admin.dashboard

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.ErrorOutline
import androidx.compose.material.icons.filled.Group
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.Opacity
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material.icons.filled.SyncProblem
import androidx.compose.material.icons.filled.WarningAmber
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.usecase.dashboard.ResumenAdmin
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun AdminDashboardScreen(viewModel: AdminDashboardViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion("Dashboard", subtitulo = "Resumen general de la operación de hoy")
        if (estado.cargando || estado.resumen == null) {
            IndicadorCarga(mensaje = "Cargando resumen…")
        } else {
            TarjetasResumen(estado.resumen!!)
        }
    }
}

private data class TarjetaResumenData(val etiqueta: String, val valor: String, val icono: ImageVector, val color: Color)

@Composable
private fun TarjetasResumen(resumen: ResumenAdmin) {
    val tarjetas = listOf(
        TarjetaResumenData("Litros del día", formatearLitros(resumen.litrosHoy), Icons.Filled.Opacity, Colores.brand),
        TarjetaResumenData("Entregas del día", resumen.entregasHoy.toString(), Icons.Filled.Inventory2, Colores.info),
        TarjetaResumenData("Proveedores activos", resumen.proveedoresActivos.toString(), Icons.Filled.Storefront, Colores.brand),
        TarjetaResumenData("Acopiadores activos", resumen.acopiadoresActivos.toString(), Icons.Filled.Group, Colores.info),
        TarjetaResumenData("Jornadas abiertas", resumen.jornadasAbiertas.toString(), Icons.Filled.CalendarMonth, Colores.secundario),
        TarjetaResumenData("Entregas pendientes", resumen.entregasPendientes.toString(), Icons.Filled.SyncProblem, Colores.advertencia),
        TarjetaResumenData("Entregas con error", resumen.entregasConError.toString(), Icons.Filled.ErrorOutline, Colores.peligro),
        TarjetaResumenData("Entregas en conflicto", resumen.entregasEnConflicto.toString(), Icons.Filled.WarningAmber, Colores.advertencia),
    )
    LazyVerticalGrid(
        columns = GridCells.Adaptive(minSize = 168.dp),
        contentPadding = PaddingValues(Espaciado.l),
        horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
        verticalArrangement = Arrangement.spacedBy(Espaciado.s),
        modifier = Modifier.fillMaxSize(),
    ) {
        items(tarjetas) { dato ->
            TarjetaEstadistica(etiqueta = dato.etiqueta, valor = dato.valor, icono = dato.icono, color = dato.color)
        }
    }
}
