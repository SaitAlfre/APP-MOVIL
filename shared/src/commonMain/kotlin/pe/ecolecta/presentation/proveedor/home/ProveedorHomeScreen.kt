package pe.ecolecta.presentation.proveedor.home

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Inventory2
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

@Composable
fun ProveedorHomeScreen(viewModel: ProveedorHomeViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu información…")
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion(titulo = "Hola, ${estado.nombreProveedor}", subtitulo = "Código ${estado.codigoProveedor}")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            if (estado.estadoProveedor != EstadoProveedor.ACTIVO) {
                MensajeEstadoProveedor(estado.estadoProveedor)
            }

            TarjetaEstadistica(
                etiqueta = "LITROS DE HOY · ${estado.entregasHoy} entrega(s)",
                valor = formatearLitros(estado.litrosHoy),
                icono = Icons.Filled.WaterDrop,
                color = Colores.brand,
            )

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica(
                    etiqueta = "Litros esta semana",
                    valor = formatearLitros(estado.litrosSemana),
                    icono = Icons.Filled.WaterDrop,
                    color = Colores.info,
                    modifier = Modifier.weight(1f),
                )
                TarjetaEstadistica(
                    etiqueta = "Entregas esta semana",
                    valor = estado.entregasSemana.toString(),
                    icono = Icons.Filled.Inventory2,
                    color = Colores.secundario,
                    modifier = Modifier.weight(1f),
                )
            }

            Text(
                "Últimas entregas",
                color = Colores.textPrimary,
                style = MaterialTheme.typography.titleMedium,
                modifier = Modifier.padding(top = Espaciado.s),
            )

            if (estado.sinEntregas) {
                EstadoVacio(
                    titulo = "Aún no tienes entregas registradas",
                    descripcion = "Las entregas que registre el acopiador en tu zona aparecerán aquí automáticamente.",
                    icono = Icons.Filled.WaterDrop,
                )
            } else {
                estado.ultimasEntregas.forEach { entrega ->
                    Tarjeta {
                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                            Column {
                                Text(formatearLitros(entrega.litros), color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
                                Text(formatearFechaHora(entrega.registradoEn), color = Colores.textSecundario, style = MaterialTheme.typography.bodySmall)
                            }
                            ChipEstado(
                                if (entrega.anulada) "ANULADA" else entrega.syncState.name,
                                if (entrega.anulada) Colores.peligro else Colores.info,
                            )
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun MensajeEstadoProveedor(estado: EstadoProveedor) {
    val (texto, tipo) = when (estado) {
        EstadoProveedor.SUSPENDIDO -> "Tu cuenta se encuentra suspendida." to TipoBanner.ADVERTENCIA
        EstadoProveedor.RETIRADO -> "Proveedor retirado. Solo puedes consultar tu historial." to TipoBanner.ERROR
        EstadoProveedor.ACTIVO -> return
    }
    Banner(mensaje = texto, tipo = tipo)
}
