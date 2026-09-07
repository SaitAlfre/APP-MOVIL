package pe.ecolecta.presentation.admin.zonas

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
import androidx.compose.material.icons.filled.Place
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
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun ZonasScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    viewModel: ZonasViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Zonas",
            subtitulo = "${estado.zonas.size} zonas de recolección",
            accion = { BotonAccion("Nueva", alCrear, icono = Icons.Filled.Add) },
        )
        estado.error?.let {
            Column(Modifier.padding(horizontal = Espaciado.l, vertical = Espaciado.xs)) { Banner(it, TipoBanner.ERROR) }
        }
        if (estado.zonas.isEmpty()) {
            EstadoVacio(
                titulo = "No hay zonas registradas",
                descripcion = "Crea la primera zona para poder asignar proveedores y jornadas.",
                icono = Icons.Filled.Place,
                textoAccion = "Crear zona",
                alPresionarAccion = alCrear,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.zonas, key = { it.id }) { zona ->
                    Tarjeta(onClick = { alEditar(zona.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Text(
                            zona.nombre,
                            style = MaterialTheme.typography.titleMedium,
                            color = Colores.textPrimary,
                            modifier = Modifier.weight(1f, fill = false),
                        )
                            ChipEstado(
                                if (zona.activo) "ACTIVA" else "INACTIVA",
                                if (zona.activo) Colores.exito else Colores.peligro,
                            )
                        }
                    }
                }
            }
        }
    }
}
