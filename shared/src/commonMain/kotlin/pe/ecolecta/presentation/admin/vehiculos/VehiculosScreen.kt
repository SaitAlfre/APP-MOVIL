package pe.ecolecta.presentation.admin.vehiculos

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.LocalShipping
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonAccion
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun VehiculosScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    viewModel: VehiculosViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Vehículos",
            subtitulo = "${estado.vehiculos.size} registrados",
            accion = { BotonAccion("Nuevo", alCrear, icono = Icons.Filled.Add) },
        )
        estado.error?.let {
            Column(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) { Banner(it, TipoBanner.ERROR) }
        }
        if (estado.cargando) {
            IndicadorCarga()
        } else if (estado.vehiculos.isEmpty()) {
            EstadoVacio(
                titulo = "No hay vehículos registrados",
                descripcion = "Agrega el primer vehículo para asignarlo a una jornada.",
                icono = Icons.Filled.LocalShipping,
                textoAccion = "Crear vehículo",
                alPresionarAccion = alCrear,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.vehiculos, key = { it.id }) { vehiculo ->
                    Tarjeta(onClick = { alEditar(vehiculo.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f, fill = false)) {
                                Text(vehiculo.nombre, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                                Text(vehiculo.placa, style = MaterialTheme.typography.bodySmall, color = Colores.textSecundario)
                            }
                            ChipEstado(
                                if (vehiculo.activo) "ACTIVO" else "INACTIVO",
                                if (vehiculo.activo) Colores.exito else Colores.peligro,
                            )
                        }
                    }
                }
            }
        }
    }
}
