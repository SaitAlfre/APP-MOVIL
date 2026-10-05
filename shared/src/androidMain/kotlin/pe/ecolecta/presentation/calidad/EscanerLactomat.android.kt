package pe.ecolecta.presentation.calidad

import android.Manifest
import android.content.pm.PackageManager
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DocumentScanner
import androidx.compose.material.icons.filled.PhotoLibrary
import androidx.compose.runtime.*
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import pe.ecolecta.domain.calidad.LineaTextoComprobante
import pe.ecolecta.domain.calidad.ordenarTextoComprobante
import pe.ecolecta.presentation.design.BotonSecundario
import java.io.File

@Composable
actual fun EscanerComprobante(alDetectarTexto: (String) -> Unit, alError: (String) -> Unit) {
    val context = LocalContext.current
    val reconocido by rememberUpdatedState(alDetectarTexto)
    val error by rememberUpdatedState(alError)
    val scope = rememberCoroutineScope()
    val reconocedor = remember { TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS) }
    var leyendo by remember { mutableStateOf(false) }
    // Mantiene el destino de la cámara si Android recrea la pantalla mientras se toma la foto.
    var fotoPendiente by rememberSaveable { mutableStateOf<String?>(null) }
    var activo by remember { mutableStateOf(true) }
    DisposableEffect(reconocedor) { onDispose { activo = false; reconocedor.close() } }
    fun leerFoto(uri: Uri, archivoTemporal: File? = null) {
        leyendo = true
        scope.launch {
            try {
                val imagen = withContext(Dispatchers.IO) { InputImage.fromFilePath(context, uri) }
                reconocedor.process(imagen)
                    .addOnSuccessListener { resultado ->
                        if (activo) {
                            leyendo = false
                            val lineas = resultado.textBlocks.flatMap { it.lines }
                            val ubicadas = lineas.mapNotNull { linea ->
                                linea.boundingBox?.let { caja ->
                                    LineaTextoComprobante(linea.text, caja.left.toFloat(), caja.exactCenterY(), caja.height().toFloat())
                                }
                            }
                            val texto = if (ubicadas.size == lineas.size) ordenarTextoComprobante(ubicadas) else resultado.text
                            if (texto.isBlank()) error("No se encontró texto legible. Acerca el comprobante y mejora la iluminación.")
                            else reconocido(texto)
                        }
                    }
                    .addOnFailureListener {
                        if (activo) {
                            leyendo = false
                            error("No se pudo leer el comprobante. Intenta con otra foto o ingresa los valores manualmente.")
                        }
                    }
                    .addOnCompleteListener { archivoTemporal?.delete() }
            } catch (e: CancellationException) {
                archivoTemporal?.delete()
                throw e
            } catch (e: Exception) {
                archivoTemporal?.delete()
                if (activo) {
                    leyendo = false
                    error("No se pudo abrir la imagen. Elige otra foto del comprobante.")
                }
            }
        }
    }
    val camara = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { tomada ->
        val archivo = fotoPendiente?.let { File(it) }
        fotoPendiente = null
        if (tomada && archivo != null) leerFoto(Uri.fromFile(archivo), archivo)
        else archivo?.delete()
    }
    val galeria = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        if (uri != null) leerFoto(uri)
    }
    fun abrirCamara() {
        try {
            val carpeta = File(context.cacheDir, "comprobantes").apply { mkdirs() }
            val archivo = File.createTempFile("comprobante-", ".jpg", carpeta)
            fotoPendiente = archivo.absolutePath
            val uri = FileProvider.getUriForFile(context, "${context.packageName}.qr", archivo)
            camara.launch(uri)
        } catch (e: Exception) {
            fotoPendiente?.let { File(it).delete() }
            fotoPendiente = null
            error("No se pudo abrir la cámara. Puedes elegir una foto de la galería o ingresar los valores manualmente.")
        }
    }
    val permiso = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { concedido ->
        if (concedido) abrirCamara() else error("Permite el acceso a la cámara o elige una foto de la galería.")
    }
    val ocupado = leyendo || fotoPendiente != null
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        BotonSecundario(
            texto = if (leyendo) "Leyendo comprobante…" else "Escanear con cámara",
            icono = Icons.Filled.DocumentScanner, habilitado = !ocupado,
            onClick = {
                if (ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) abrirCamara()
                else permiso.launch(Manifest.permission.CAMERA)
            },
        )
        BotonSecundario(
            texto = "Elegir foto del comprobante",
            icono = Icons.Filled.PhotoLibrary, habilitado = !ocupado,
            onClick = {
                try { galeria.launch("image/*") }
                catch (e: Exception) { error("No se pudo abrir la galería. Prueba escanear con la cámara.") }
            },
        )
    }
}

