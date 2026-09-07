package pe.ecolecta.presentation.admin.proveedores

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Storefront
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.presentation.design.BotonAccion
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta

@Composable
fun ProveedoresScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    viewModel: ProveedoresViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Proveedores",
            subtitulo = "${estado.proveedoresFiltrados.size} de ${estado.proveedores.size} proveedores",
            accion = { BotonAccion("Nuevo", alCrear, icono = Icons.Filled.Add) },
        )
        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
            CampoTexto(
                estado.filtroTexto,
                { viewModel.onEvent(ProveedoresUiEvent.FiltroTextoCambia(it)) },
                "Buscar por código, nombre o DNI",
            )
            LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                item { ChipSeleccionable("Todas", estado.filtroZonaId == null) { viewModel.onEvent(ProveedoresUiEvent.FiltroZonaCambia(null)) } }
                items(estado.zonas, key = { it.id }) { zona ->
                    ChipSeleccionable(zona.nombre, estado.filtroZonaId == zona.id) {
                        viewModel.onEvent(ProveedoresUiEvent.FiltroZonaCambia(zona.id))
                    }
                }
            }
        }
        if (estado.proveedoresFiltrados.isEmpty()) {
            EstadoVacio(
                titulo = "No hay proveedores que coincidan",
                descripcion = "Ajusta la búsqueda/filtro o registra un nuevo proveedor.",
                icono = Icons.Filled.Storefront,
                textoAccion = "Crear proveedor",
                alPresionarAccion = alCrear,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.proveedoresFiltrados, key = { it.id }) { proveedor ->
                    Tarjeta(onClick = { alEditar(proveedor.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(
                                    "${proveedor.codigo} · ${proveedor.nombres}",
                                    style = MaterialTheme.typography.titleMedium,
                                    color = Colores.textPrimary,
                                )
                                Text(
                                    "${estado.nombreZona(proveedor.zonaId)} · ${proveedor.tachos} tachos · ${proveedor.capacidadTotalL} L",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Colores.textSecundario,
                                )
                            }
                            ChipEstado(
                                proveedor.estado.name,
                                when (proveedor.estado) {
                                    EstadoProveedor.ACTIVO -> Colores.exito
                                    EstadoProveedor.SUSPENDIDO -> Colores.advertencia
                                    EstadoProveedor.RETIRADO -> Colores.peligro
                                },
                            )
                        }
                    }
                }
            }
        }
    }
}
