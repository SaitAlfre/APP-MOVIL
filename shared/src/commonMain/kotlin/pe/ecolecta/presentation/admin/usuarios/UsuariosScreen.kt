package pe.ecolecta.presentation.admin.usuarios

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
import androidx.compose.material.icons.filled.Group
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.presentation.design.BotonAccion
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta

@Composable
fun UsuariosScreen(
    alCrear: () -> Unit,
    alEditar: (String) -> Unit,
    viewModel: UsuariosViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    Column(Modifier.fillMaxSize()) {
        EncabezadoSeccion(
            "Usuarios",
            subtitulo = "${estado.usuariosFiltrados.size} registrados",
            accion = { BotonAccion("Nuevo", alCrear, icono = Icons.Filled.Add) },
        )
        Column(Modifier.padding(horizontal = Espaciado.l)) {
            CampoTexto(
                valor = estado.filtroTexto,
                onValorCambia = { viewModel.onEvent(UsuariosUiEvent.FiltroTextoCambia(it)) },
                etiqueta = "Buscar por nombre, usuario o DNI",
            )
        }
        if (estado.usuariosFiltrados.isEmpty()) {
            EstadoVacio(
                titulo = "No hay usuarios que coincidan",
                descripcion = "Ajusta la búsqueda o crea un nuevo usuario del sistema.",
                icono = Icons.Filled.Group,
                textoAccion = "Crear usuario",
                alPresionarAccion = alCrear,
            )
        } else {
            LazyColumn(
                Modifier.fillMaxSize().padding(horizontal = Espaciado.l, vertical = Espaciado.m),
                verticalArrangement = Arrangement.spacedBy(Espaciado.s),
            ) {
                items(estado.usuariosFiltrados, key = { it.id }) { usuario ->
                    Tarjeta(onClick = { alEditar(usuario.id) }) {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                            Column(Modifier.weight(1f)) {
                                Text(usuario.nombres, style = MaterialTheme.typography.titleMedium, color = Colores.textPrimary)
                                Text(
                                    "@${usuario.username} · DNI ${usuario.dni} · ${usuario.roles.joinToString { it.name }}",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = Colores.textSecundario,
                                )
                            }
                            ChipEstado(
                                if (usuario.activo) "ACTIVO" else "INACTIVO",
                                if (usuario.activo) Colores.exito else Colores.peligro,
                            )
                        }
                    }
                }
            }
        }
    }
}
