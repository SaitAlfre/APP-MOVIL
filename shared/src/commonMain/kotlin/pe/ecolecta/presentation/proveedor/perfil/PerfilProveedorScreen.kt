package pe.ecolecta.presentation.proveedor.perfil

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
import androidx.compose.material.icons.filled.Sync
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Dato
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.EnlaceTexto
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun PerfilProveedorScreen(viewModel: PerfilProveedorViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu perfil…")
        return
    }

    if (estado.error != null) {
        EstadoVacio(titulo = "No se pudo cargar tu perfil", descripcion = estado.error.orEmpty())
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Mi perfil")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            estado.proveedor?.let { proveedor ->
                Tarjeta {
                    Row(
                        Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically,
                    ) {
                        Column(Modifier.weight(1f, fill = false)) {
                            Text(proveedor.nombres, color = Colores.textPrimary, style = MaterialTheme.typography.titleLarge)
                            Text("Código ${proveedor.codigo}", color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
                        }
                        ChipEstado(proveedor.estado.name, colorEstado(proveedor.estado), mostrarPunto = false)
                    }
                }

                Tarjeta {
                    Dato("DNI", proveedor.dni)
                    Dato("Teléfono", proveedor.telefono ?: "—")
                    Dato("Dirección", proveedor.direccion ?: "—")
                    // Zona, tachos y capacidad son un solo dato para quien lee: dónde entrega y con
                    // cuánto. El diseño los junta en una línea en vez de cuatro filas casi vacías.
                    Dato(
                        "Zona · Tachos",
                        "${estado.nombreZona} · ${proveedor.tachos} tachos · ${formatearLitros(proveedor.capacidadTachoL)} c/u",
                        ultimo = true,
                    )
                }

                Tarjeta {
                    Text("ESTADO DE SINCRONIZACIÓN", color = Colores.textSecundario, style = MaterialTheme.typography.labelMedium)
                    Row(
                        Modifier.fillMaxWidth().padding(top = Espaciado.s),
                        horizontalArrangement = Arrangement.spacedBy(Espaciado.xl),
                    ) {
                        DatoSync("Pendientes", estado.resumenSync.pendientes, Colores.info)
                        DatoSync("Sincronizadas", estado.resumenSync.sincronizadas, Colores.exito)
                        DatoSync("Errores", estado.resumenSync.errores, Colores.peligro)
                    }
                }

                estado.mensajeSincronizar?.let { Banner(mensaje = it, tipo = TipoBanner.INFO) }

                BotonSecundario("Actualizar información", viewModel::sincronizar, icono = Icons.Filled.Sync)

                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.Center) {
                    EnlaceTexto(texto = "Cerrar sesión", onClick = viewModel::cerrarSesion, color = Colores.peligro)
                }

                Spacer(Modifier.height(Espaciado.l))
            }
        }
    }
}

@Composable
private fun colorEstado(estado: EstadoProveedor) = when (estado) {
    EstadoProveedor.ACTIVO -> Colores.exito
    EstadoProveedor.SUSPENDIDO -> Colores.advertencia
    EstadoProveedor.RETIRADO -> Colores.peligro
}

@Composable
private fun DatoSync(etiqueta: String, valor: Int, color: Color) {
    Column {
        Text(valor.toString(), color = color, style = MaterialTheme.typography.headlineSmall)
        Text(etiqueta, color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
    }
}
