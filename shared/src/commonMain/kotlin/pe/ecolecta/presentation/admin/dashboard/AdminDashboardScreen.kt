package pe.ecolecta.presentation.admin.dashboard

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.lazy.grid.GridItemSpan
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.MaterialTheme
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.design.Tarjeta
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
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun AdminDashboardScreen(viewModel: AdminDashboardViewModel = koinViewModel(), alNavegar: (Pantalla) -> Unit = {}) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion("Resumen de la operación", subtitulo = "Datos guardados en este dispositivo · Todas las zonas")
        TextButton(onClick = viewModel::cargar, enabled = !estado.cargando) { Text("Actualizar resumen") }
        when {
            estado.error != null -> EstadoVacio(
                titulo = "No se pudo cargar el resumen",
                descripcion = estado.error.orEmpty(),
                icono = Icons.Filled.ErrorOutline,
                textoAccion = "Reintentar",
                alPresionarAccion = viewModel::cargar,
            )
            estado.cargando || estado.resumen == null -> IndicadorCarga(mensaje = "Cargando resumen…")
            else -> TarjetasResumen(estado.resumen!!, alNavegar)
        }
    }
}

private data class TarjetaResumenData(val etiqueta: String, val valor: String, val icono: ImageVector, val color: Color)

@Composable
private fun TarjetasResumen(resumen: ResumenAdmin, alNavegar: (Pantalla) -> Unit) {
    val tarjetas = listOf(
        TarjetaResumenData("Litros del día", formatearLitros(resumen.litrosHoy), Icons.Filled.Opacity, Colores.brand),
        TarjetaResumenData("Entregas del día", resumen.entregasHoy.toString(), Icons.Filled.Inventory2, Colores.info),
        TarjetaResumenData("Proveedores activos", resumen.proveedoresActivos.toString(), Icons.Filled.Storefront, Colores.brand),
        TarjetaResumenData("Acopiadores habilitados", resumen.acopiadoresActivos.toString(), Icons.Filled.Group, Colores.info),
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
        item(span = { GridItemSpan(maxLineSpan) }) { Text("Hoy", style = MaterialTheme.typography.titleMedium) }
        items(tarjetas.take(2)) { dato ->
            TarjetaEstadistica(etiqueta = dato.etiqueta, valor = dato.valor, icono = dato.icono, color = dato.color)
        }
        item(span = { GridItemSpan(maxLineSpan) }) { Text("Totales y pendientes · Todas las fechas", style = MaterialTheme.typography.titleMedium) }
        items(tarjetas.drop(2)) { dato ->
            TarjetaEstadistica(etiqueta = dato.etiqueta, valor = dato.valor, icono = dato.icono, color = dato.color)
        }
        item(span = { GridItemSpan(maxLineSpan) }) {
            pe.ecolecta.presentation.admin.supervision.ResumenCalidadAdmin({ alNavegar(Pantalla.AdminCalidad()) })
        }
        item(span = { GridItemSpan(maxLineSpan) }) {
            Tarjeta {
                Text("Supervisar la operación", style = MaterialTheme.typography.titleMedium)
                if (resumen.entregasConError + resumen.entregasEnConflicto > 0) {
                    Text("Hay entregas con errores o conflictos que requieren atención.", color = Colores.peligro)
                }
                TextButton(onClick = { alNavegar(Pantalla.AdminEntregas) }) { Text("Consultar entregas y pendientes →") }
                TextButton(onClick = { alNavegar(Pantalla.AdminConflictos) }) { Text("Revisar conflictos →") }
                TextButton(onClick = { alNavegar(Pantalla.AdminCalidad()) }) { Text("Consultar control de calidad →") }
                TextButton(onClick = { alNavegar(Pantalla.AdminProveedores) }) { Text("Ver proveedores →") }
            }
        }
    }
}
