package pe.ecolecta.presentation.proveedor.home

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
import androidx.compose.material.icons.automirrored.filled.ReceiptLong
import androidx.compose.material.icons.filled.ErrorOutline
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
import pe.ecolecta.presentation.design.ChipSync
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TarjetaEstadistica
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearLitros
import pe.ecolecta.presentation.proveedor.FilaEntrega

@Composable
fun ProveedorHomeScreen(
    alVerMiRuta: () -> Unit,
    viewModel: ProveedorHomeViewModel = koinViewModel(),
) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu información…")
        return
    }

    if (estado.error != null) {
        EstadoVacio(
            titulo = "No se pudo cargar tu información",
            descripcion = estado.error.orEmpty(),
            icono = Icons.Filled.ErrorOutline,
        )
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion(titulo = "Hola, ${estado.nombreProveedor}", subtitulo = "Código ${estado.codigoProveedor}")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            if (estado.estadoProveedor != EstadoProveedor.ACTIVO) {
                MensajeEstadoProveedor(estado.estadoProveedor)
            }

            TarjetaEstadistica(
                etiqueta = "LITROS DE HOY · ${estado.entregasHoy} ${if (estado.entregasHoy == 1) "entrega" else "entregas"}",
                valor = formatearLitros(estado.litrosHoy),
                icono = Icons.Filled.WaterDrop,
                color = Colores.brand,
                colorValor = Colores.brandText,
                iconoEnLinea = true,
                estiloValor = MaterialTheme.typography.headlineLarge,
            )

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                TarjetaEstadistica(
                    etiqueta = "Litros esta semana",
                    valor = formatearLitros(estado.litrosSemana),
                    icono = Icons.Filled.WaterDrop,
                    color = Colores.info,
                    iconoEnLinea = true,
                    modifier = Modifier.weight(1f),
                )
                TarjetaEstadistica(
                    etiqueta = "Entregas semana",
                    valor = estado.entregasSemana.toString(),
                    icono = Icons.AutoMirrored.Filled.ReceiptLong,
                    color = Colores.secundario,
                    iconoEnLinea = true,
                    modifier = Modifier.weight(1f),
                )
            }

            Text(
                "Últimas entregas",
                color = Colores.textPrimary,
                style = MaterialTheme.typography.titleLarge,
                modifier = Modifier.padding(top = Espaciado.xs),
            )

            if (estado.sinEntregas) {
                EstadoVacio(
                    titulo = "Aún no tienes entregas registradas",
                    descripcion = "Las entregas que registre el acopiador en tu zona aparecerán aquí automáticamente.",
                    icono = Icons.Filled.WaterDrop,
                )
            } else {
                estado.ultimasEntregas.forEach { entrega ->
                    FilaEntrega(entrega)
                }
            }

            TarjetaSiguienteAcopio(alVerMiRuta)

            Spacer(Modifier.height(Espaciado.l))
        }
    }
}

/**
 * Cierre de la pantalla de inicio: recuerda al proveedor que puede seguir al acopiador en vivo y
 * lleva directo a esa pestaña, que de otro modo solo se descubre explorando la barra inferior.
 */
@Composable
private fun TarjetaSiguienteAcopio(alVerMiRuta: () -> Unit) {
    Tarjeta(onClick = alVerMiRuta) {
        Text("Tu siguiente acopio", color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
        Spacer(Modifier.height(Espaciado.xxs))
        Text("Sigue la ruta del acopiador", color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
        Text("para conocer su ubicación.", color = Colores.textSecundario, style = MaterialTheme.typography.bodyMedium)
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
