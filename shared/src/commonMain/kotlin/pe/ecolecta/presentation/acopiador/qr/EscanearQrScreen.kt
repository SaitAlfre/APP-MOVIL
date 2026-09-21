package pe.ecolecta.presentation.acopiador.qr

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.CheckCircle
import androidx.compose.material.icons.filled.QrCodeScanner
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import org.koin.compose.viewmodel.koinViewModel
import org.koin.core.parameter.parametersOf
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.presentation.acopiador.registro.FormularioEntrega
import pe.ecolecta.presentation.acopiador.registro.RegistroEntregaViewModel
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonPrimario
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.design.ChipEstado
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Dato
import pe.ecolecta.presentation.design.DivisorSutil
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.design.formatearLitros
import qrscanner.CameraLens
import qrscanner.OverlayShape
import qrscanner.QrScanner

/** Flujo: Escanear QR → mostrar proveedor → ingresar litros → registrar entrega, todo en esta misma pantalla. */
@Composable
fun EscanearQrScreen(
    alFinalizar: () -> Unit,
    viewModel: EscanearQrViewModel = koinViewModel(),
) {
    val uiState by viewModel.uiState.collectAsState()

    when (val actual = uiState.estado) {
        EstadoEscaneoQr.Escaneando -> VistaEscaneando(
            onCodigoEscaneado = viewModel::onCodigoEscaneado,
            onError = viewModel::onErrorLectura,
        )
        is EstadoEscaneoQr.Resultado -> VistaResultado(
            resultado = actual,
            alEscanearOtro = viewModel::reintentar,
            alFinalizar = alFinalizar,
        )
        is EstadoEscaneoQr.Error -> EstadoVacio(
            titulo = "No se pudo identificar al proveedor",
            descripcion = actual.mensaje,
            icono = Icons.Filled.QrCodeScanner,
            textoAccion = "Intentar de nuevo",
            alPresionarAccion = viewModel::reintentar,
        )
    }
}

@Composable
private fun VistaEscaneando(onCodigoEscaneado: (String) -> Unit, onError: (String) -> Unit) {
    Column(
        Modifier.fillMaxSize().padding(horizontal = Espaciado.l),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Spacer(Modifier.height(Espaciado.l))
        Text(
            "Apunta la cámara al código QR",
            style = MaterialTheme.typography.titleMedium,
            color = Colores.textPrimary,
            textAlign = TextAlign.Center,
        )
        Text(
            "del proveedor",
            style = MaterialTheme.typography.bodyMedium,
            color = Colores.textSecundario,
            textAlign = TextAlign.Center,
        )
        Spacer(Modifier.height(Espaciado.m))

        Box(Modifier.fillMaxWidth().weight(1f)) {
            QrScanner(
                modifier = Modifier.fillMaxSize().clip(MaterialTheme.shapes.large),
                flashlightOn = false,
                cameraLens = CameraLens.Back,
                openImagePicker = false,
                onCompletion = onCodigoEscaneado,
                imagePickerHandler = {},
                onFailure = onError,
                overlayShape = OverlayShape.Square,
                permissionDeniedView = {
                    EstadoVacio(
                        titulo = "Se necesita acceso a la cámara",
                        descripcion = "Activa el permiso de cámara en los ajustes del dispositivo para escanear el QR del proveedor.",
                        icono = Icons.Filled.CameraAlt,
                    )
                },
            )
        }

        Spacer(Modifier.height(Espaciado.s))
        Text(
            "Busca el código QR dentro del recuadro",
            style = MaterialTheme.typography.bodySmall,
            color = Colores.textSecundario,
            textAlign = TextAlign.Center,
        )
        Spacer(Modifier.height(Espaciado.m))
        TarjetaConsejo()
        Spacer(Modifier.height(Espaciado.l))
    }
}

/**
 * Las dos cosas que desatascan un escaneo que no prende: más luz, o dejarlo y escribir los litros
 * a mano. Está siempre visible porque el acopiador escanea al aire libre y a contraluz.
 */
@Composable
private fun TarjetaConsejo() {
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = MaterialTheme.shapes.medium,
        color = Colores.brandContainer,
    ) {
        Row(
            Modifier.padding(Espaciado.m),
            horizontalArrangement = Arrangement.spacedBy(Espaciado.s),
        ) {
            Icon(Icons.Filled.CameraAlt, contentDescription = null, tint = Colores.brandText, modifier = Modifier.size(20.dp))
            Column {
                Text("Consejo", style = MaterialTheme.typography.titleSmall, color = Colores.brandText)
                Text(
                    "Asegúrate de tener buena iluminación.",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.onBrandContainer,
                )
                Text(
                    "Puedes registrar manualmente si es necesario.",
                    style = MaterialTheme.typography.bodySmall,
                    color = Colores.onBrandContainer,
                )
            }
        }
    }
}

@Composable
private fun VistaResultado(resultado: EstadoEscaneoQr.Resultado, alEscanearOtro: () -> Unit, alFinalizar: () -> Unit) {
    val proveedor = resultado.proveedor
    val puedeRegistrar = proveedor.estado == EstadoProveedor.ACTIVO && resultado.zonaCoincideConJornada

    // Reutiliza el mismo ViewModel/caso de uso que el registro manual: el proveedor detectado por
    // el QR simplemente llega ya preseleccionado, sin duplicar lógica de guardado/duplicados/outbox.
    val formViewModel: RegistroEntregaViewModel =
        koinViewModel(key = "registro-${proveedor.id}", parameters = { parametersOf(proveedor.id) })
    val formEstado by formViewModel.uiState.collectAsState()
    var confirmado by remember(proveedor.id) { mutableStateOf(false) }

    LaunchedEffect(formEstado.guardadoExitoso) {
        if (formEstado.guardadoExitoso) {
            confirmado = true
            formViewModel.confirmarNavegacion()
        }
    }

    if (confirmado) {
        VistaConfirmacion(
            proveedor = proveedor,
            litros = formEstado.litros.toDoubleOrNull() ?: 0.0,
            alEscanearOtro = alEscanearOtro,
            alFinalizar = alFinalizar,
        )
        return
    }

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
        Banner("Proveedor encontrado", TipoBanner.EXITO)

        Tarjeta {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f, fill = false)) {
                    Text(proveedor.nombres, style = MaterialTheme.typography.titleLarge, color = Colores.textPrimary)
                    Text("Código ${proveedor.codigo}", style = MaterialTheme.typography.bodyMedium, color = Colores.textSecundario)
                }
                ChipEstado(proveedor.estado.name, colorDeEstado(proveedor.estado), mostrarPunto = false)
            }
            Spacer(Modifier.height(Espaciado.s))
            DivisorSutil()
            Spacer(Modifier.height(Espaciado.s))
            Dato("Zona", resultado.nombreZona)
            Dato("Tachos", "${proveedor.tachos} × ${formatearLitros(proveedor.capacidadTachoL)}", ultimo = true)
        }

        mensajeBloqueo(proveedor, resultado)?.let { Banner(it, TipoBanner.ADVERTENCIA) }

        if (puedeRegistrar) {
            DivisorSutil()
            Text("Registrar entrega", style = MaterialTheme.typography.titleSmall, color = Colores.textPrimary)
            FormularioEntrega(estado = formEstado, viewModel = formViewModel)
        }

        BotonSecundario("Escanear otro código", alEscanearOtro, icono = Icons.Filled.QrCodeScanner)
    }
}

@Composable
private fun VistaConfirmacion(proveedor: Proveedor, litros: Double, alEscanearOtro: () -> Unit, alFinalizar: () -> Unit) {
    Column(
        Modifier.fillMaxSize().padding(Espaciado.l),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center,
    ) {
        Box(
            Modifier.size(72.dp).clip(CircleShape).background(Colores.exito.copy(alpha = 0.14f)),
            contentAlignment = Alignment.Center,
        ) {
            Icon(Icons.Filled.CheckCircle, contentDescription = null, tint = Colores.exito, modifier = Modifier.size(40.dp))
        }
        Spacer(Modifier.height(Espaciado.m))
        Text("Entrega registrada", style = MaterialTheme.typography.headlineSmall, color = Colores.textPrimary)
        Spacer(Modifier.height(Espaciado.xxs))
        Text(
            "${proveedor.codigo} · ${proveedor.nombres} · ${formatearLitros(litros)}",
            style = MaterialTheme.typography.bodyMedium,
            color = Colores.textSecundario,
        )
        Spacer(Modifier.height(Espaciado.xxl))
        BotonPrimario("Escanear otro proveedor", alEscanearOtro, icono = Icons.Filled.QrCodeScanner)
        Spacer(Modifier.height(Espaciado.s))
        BotonSecundario("Volver al inicio", alFinalizar)
    }
}

private fun mensajeBloqueo(proveedor: Proveedor, resultado: EstadoEscaneoQr.Resultado): String? = when {
    proveedor.estado == EstadoProveedor.SUSPENDIDO -> "Este proveedor está suspendido. No se le pueden registrar entregas."
    proveedor.estado == EstadoProveedor.RETIRADO -> "Este proveedor fue retirado. No se le pueden registrar entregas."
    !resultado.zonaCoincideConJornada -> "Este proveedor pertenece a otra zona: no puedes registrarle una entrega en tu jornada actual."
    else -> null
}

@Composable
private fun colorDeEstado(estado: EstadoProveedor) = when (estado) {
    EstadoProveedor.ACTIVO -> Colores.exito
    EstadoProveedor.SUSPENDIDO -> Colores.advertencia
    EstadoProveedor.RETIRADO -> Colores.peligro
}

