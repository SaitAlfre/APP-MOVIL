package pe.ecolecta.presentation.admin.proveedores

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Phone
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.presentation.design.BarraSuperior
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.CampoTexto
import pe.ecolecta.presentation.design.ChipSeleccionable
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner

@Composable
fun ProveedorFormScreen(
    id: String?,
    alGuardar: () -> Unit,
    alVolver: () -> Unit = alGuardar,
    viewModel: ProveedorFormViewModel = koinViewModel(parameters = { parametersOf(id) }),
) {
    val estado by viewModel.uiState.collectAsState()

    LaunchedEffect(estado.guardadoExitoso) { if (estado.guardadoExitoso) alGuardar() }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        BarraSuperior(if (estado.esEdicion) "Editar proveedor" else "Nuevo proveedor", alVolver = alVolver)
        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            Tarjeta {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    CampoTexto(
                        estado.codigo,
                        { viewModel.onEvent(ProveedorFormUiEvent.CodigoCambia(it)) },
                        "Código",
                        soloLectura = estado.esEdicion,
                        iconoInicial = Icons.Filled.Badge,
                    )
                    CampoTexto(
                        estado.nombres,
                        { viewModel.onEvent(ProveedorFormUiEvent.NombresCambia(it)) },
                        "Nombres",
                        iconoInicial = Icons.Filled.Person,
                    )
                    CampoTexto(estado.dni, { viewModel.onEvent(ProveedorFormUiEvent.DniCambia(it)) }, "DNI", iconoInicial = Icons.Filled.Badge)
                    CampoTexto(
                        estado.telefono,
                        { viewModel.onEvent(ProveedorFormUiEvent.TelefonoCambia(it)) },
                        "Teléfono",
                        iconoInicial = Icons.Filled.Phone,
                    )
                    CampoTexto(
                        estado.direccion,
                        { viewModel.onEvent(ProveedorFormUiEvent.DireccionCambia(it)) },
                        "Dirección",
                        iconoInicial = Icons.Filled.Home,
                    )
                }
            }

            Tarjeta {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                    Text("Zona", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                    LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                        items(estado.zonas, key = { it.id }) { zona ->
                            ChipSeleccionable(zona.nombre, zona.id == estado.zonaId) {
                                viewModel.onEvent(ProveedorFormUiEvent.ZonaCambia(zona.id))
                            }
                        }
                    }
                }
            }

            Tarjeta {
                Column(verticalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                    CampoTexto(
                        estado.tachos,
                        { viewModel.onEvent(ProveedorFormUiEvent.TachosCambia(it)) },
                        "Cantidad de tachos",
                        iconoInicial = Icons.Filled.Inventory2,
                    )
                    CampoTexto(
                        estado.capacidadTachoL,
                        { viewModel.onEvent(ProveedorFormUiEvent.CapacidadCambia(it)) },
                        "Capacidad por tacho (L)",
                        error = estado.error,
                    )
                }
            }

            if (estado.esEdicion) {
                Tarjeta {
                    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                        Text("Estado", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                        LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                            items(EstadoProveedor.entries) { valor ->
                                ChipSeleccionable(valor.name, valor == estado.estado) {
                                    viewModel.onEvent(ProveedorFormUiEvent.EstadoCambia(valor))
                                }
                            }
                        }
                    }
                }

                Tarjeta {
                    Column(verticalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                        Text("Cuenta de acceso (rol PROVEEDOR)", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
                        Text(
                            "Vincula el usuario con el que este proveedor inicia sesión para ver su perfil y sus entregas.",
                            style = MaterialTheme.typography.bodySmall,
                            color = Colores.textSecundario,
                        )
                        if (estado.usuariosProveedor.isEmpty()) {
                            Text(
                                "No hay usuarios con rol PROVEEDOR creados todavía. Créalo primero en Usuarios.",
                                style = MaterialTheme.typography.bodySmall,
                                color = Colores.textSecundario,
                            )
                        } else {
                            LazyRow(horizontalArrangement = Arrangement.spacedBy(Espaciado.xs)) {
                                items(estado.usuariosProveedor, key = { it.id }) { usuario ->
                                    ChipSeleccionable(usuario.username, usuario.id == estado.usuarioIdVinculado) {
                                        viewModel.onEvent(ProveedorFormUiEvent.VincularUsuario(usuario.id))
                                    }
                                }
                            }
                        }
                        val errorVinculacion = estado.errorVinculacion
                        if (errorVinculacion != null) {
                            Banner(errorVinculacion, TipoBanner.ERROR)
                        }
                    }
                }
            }

            BotonPrimario("Guardar", { viewModel.onEvent(ProveedorFormUiEvent.Guardar) }, habilitado = estado.puedeGuardar && !estado.cargando)
        }
    }
}
