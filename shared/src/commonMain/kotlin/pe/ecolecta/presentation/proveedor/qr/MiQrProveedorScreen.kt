package pe.ecolecta.presentation.proveedor.qr

import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.drawWithContent
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.rememberGraphicsLayer
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import org.koin.compose.koinInject
import org.koin.compose.viewmodel.koinViewModel
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.presentation.design.Banner
import pe.ecolecta.presentation.design.BotonSecundario
import pe.ecolecta.presentation.cargaSegura
import pe.ecolecta.presentation.design.Dato
import pe.ecolecta.presentation.design.EcolectaLogo
import pe.ecolecta.presentation.design.EncabezadoSeccion
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.EstadoVacio
import pe.ecolecta.presentation.design.IndicadorCarga
import pe.ecolecta.presentation.design.PaletaClara
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.TipoBanner
import pe.ecolecta.presentation.qr.ExportadorQr
import pe.ecolecta.presentation.qr.rememberSolicitadorPermisoAlmacenamiento
import qrgenerator.qrkitpainter.rememberQrKitPainter
import qrgenerator.shareQrCodeImage

@Composable
fun MiQrProveedorScreen(viewModel: MiQrProveedorViewModel = koinViewModel()) {
    val estado by viewModel.uiState.collectAsState()

    if (estado.cargando) {
        IndicadorCarga(mensaje = "Cargando tu QR…")
        return
    }
    if (estado.error != null) {
        EstadoVacio(titulo = "No se pudo cargar tu QR", descripcion = estado.error.orEmpty())
        return
    }
    val proveedor = estado.proveedor ?: return

    val exportadorQr = koinInject<ExportadorQr>()
    val scope = rememberCoroutineScope()
    val graphicsLayer = rememberGraphicsLayer()
    var exportando by remember { mutableStateOf(false) }
    var mensaje by remember { mutableStateOf<Pair<String, TipoBanner>?>(null) }

    fun nombreArchivo() = "qr-ecolecta-${proveedor.codigo}"

    fun guardarEnGaleria() {
        scope.launch {
            mensaje = null
            try {
                cargaSegura {
                    val bitmap = graphicsLayer.toImageBitmap()
                    exportadorQr.guardarEnGaleria(bitmap, nombreArchivo()).getOrThrow()
                }.fold(
                    onSuccess = { mensaje = "QR guardado en tu galería." to TipoBanner.EXITO },
                    onFailure = { error -> mensaje = "No se pudo guardar el QR: ${error.message}" to TipoBanner.ERROR },
                )
            } finally {
                exportando = false
            }
        }
    }

    val solicitarPermisoAlmacenamiento = rememberSolicitadorPermisoAlmacenamiento(onResultado = { concedido ->
        if (concedido) {
            guardarEnGaleria()
        } else {
            exportando = false
            mensaje = "Se necesita permiso de almacenamiento para guardar el QR en tu galería." to TipoBanner.ADVERTENCIA
        }
    })

    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState())) {
        EncabezadoSeccion("Mi QR", subtitulo = "Muéstraselo al acopiador, o descárgalo e imprímelo para llevarlo contigo")

        Column(Modifier.padding(horizontal = Espaciado.l), verticalArrangement = Arrangement.spacedBy(Espaciado.m)) {
            Column(
                Modifier
                    .fillMaxWidth()
                    .drawWithContent {
                        graphicsLayer.record { this@drawWithContent.drawContent() }
                        drawContent()
                    },
            ) {
                TarjetaQrImprimible(proveedor = proveedor, contenidoQr = estado.contenidoQr)
            }

            mensaje?.let { (texto, tipo) -> Banner(texto, tipo) }

            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(Espaciado.s)) {
                AccionQr(
                    texto = "Descargar",
                    habilitado = !exportando,
                    modifier = Modifier.weight(1f),
                    onClick = {
                        exportando = true
                        mensaje = null
                        solicitarPermisoAlmacenamiento()
                    },
                )
                AccionQr(
                    texto = "Imprimir",
                    habilitado = !exportando,
                    modifier = Modifier.weight(1f),
                    onClick = {
                        scope.launch {
                            exportando = true
                            mensaje = null
                            try {
                                cargaSegura {
                                    val bitmap = graphicsLayer.toImageBitmap()
                                    exportadorQr.imprimir(bitmap, "QR ${proveedor.codigo}")
                                }.onFailure { error -> mensaje = "No se pudo imprimir el QR: ${error.message}" to TipoBanner.ERROR }
                            } finally {
                                exportando = false
                            }
                        }
                    },
                )
                AccionQr(
                    texto = "Compartir",
                    habilitado = !exportando,
                    modifier = Modifier.weight(1f),
                    onClick = {
                        scope.launch {
                            exportando = true
                            mensaje = null
                            try {
                                cargaSegura {
                                    val bitmap = graphicsLayer.toImageBitmap()
                                    shareQrCodeImage(bitmap, nombreArchivo())
                                }.onFailure { error -> mensaje = "No se pudo compartir el QR: ${error.message}" to TipoBanner.ERROR }
                            } finally {
                                exportando = false
                            }
                        }
                    },
                )
            }

            Tarjeta {
                Dato("Código", proveedor.codigo)
                Dato("Nombre", proveedor.nombres)
                Dato("Zona", "${estado.nombreZona} · ${proveedor.tachos} tachos", ultimo = true)
            }
        }
    }
}

/**
 * Tarjeta pensada para imprimirse/compartirse: siempre fondo blanco y texto oscuro (sin importar el
 * tema claro/oscuro de la app), para que la imagen guardada o impresa se vea limpia en papel.
 * Contiene únicamente el identificador seguro del proveedor dentro del QR (ver [pe.ecolecta.domain.generarQrProveedor]).
 */
@Composable
private fun TarjetaQrImprimible(proveedor: Proveedor, contenidoQr: String) {
    Surface(modifier = Modifier.fillMaxWidth(), shape = MaterialTheme.shapes.medium, color = Color.White) {
        Column(
            Modifier.fillMaxWidth().padding(Espaciado.xl),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            EcolectaLogo(tamano = 56.dp, colorFondo = PaletaClara.brand, colorSimbolo = PaletaClara.onBrand)
            Column(Modifier.padding(top = Espaciado.m), horizontalAlignment = Alignment.CenterHorizontally) {
                Image(
                    painter = rememberQrKitPainter(data = contenidoQr),
                    contentDescription = "Código QR de ${proveedor.nombres}",
                    modifier = Modifier.size(220.dp),
                )
            }
            Text(
                proveedor.nombres,
                style = MaterialTheme.typography.titleLarge,
                color = Color.Black,
                textAlign = TextAlign.Center,
                modifier = Modifier.padding(top = Espaciado.m),
            )
            Text("Código ${proveedor.codigo}", style = MaterialTheme.typography.titleMedium, color = Color.Black)
            Text(
                "Código de identificación para acopio",
                style = MaterialTheme.typography.bodySmall,
                color = Color.DarkGray,
                textAlign = TextAlign.Center,
                modifier = Modifier.padding(top = Espaciado.xs),
            )
        }
    }
}

/** Las tres acciones comparten el ancho de la pantalla, así que van sin ícono para que el texto quepa entero. */
@Composable
private fun AccionQr(texto: String, habilitado: Boolean, modifier: Modifier = Modifier, onClick: () -> Unit) {
    BotonSecundario(texto = texto, onClick = onClick, modifier = modifier, habilitado = habilitado)
}


