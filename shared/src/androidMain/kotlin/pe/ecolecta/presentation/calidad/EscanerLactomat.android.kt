package pe.ecolecta.presentation.calidad

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.DocumentScanner
import androidx.compose.runtime.*
import androidx.compose.ui.platform.LocalContext
import androidx.core.content.ContextCompat
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import pe.ecolecta.presentation.design.BotonSecundario

@Composable
actual fun EscanerComprobante(alDetectarTexto: (String) -> Unit, alError: (String) -> Unit) {
    val context = LocalContext.current
    val reconocido by rememberUpdatedState(alDetectarTexto)
    val error by rememberUpdatedState(alError)
    val reconocedor = remember { TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS) }
    var leyendo by remember { mutableStateOf(false) }
    var activo by remember { mutableStateOf(true) }
    DisposableEffect(reconocedor) { onDispose { activo = false; reconocedor.close() } }
    val camara = rememberLauncherForActivityResult(ActivityResultContracts.TakePicturePreview()) { bitmap ->
        if(bitmap == null) leyendo = false
        else reconocedor.process(InputImage.fromBitmap(bitmap, 0))
            .addOnSuccessListener { resultado ->
                leyendo = false
                if(activo) {
                    if(resultado.text.isBlank()) error("No se encontró texto legible. Acerca el comprobante y mejora la iluminación.")
                    else reconocido(resultado.text)
                }
            }.addOnFailureListener {
                leyendo = false
                if(activo) error("No se pudo leer el comprobante. Puedes ingresar los valores manualmente.")
            }
    }
    fun abrir() {
        try { leyendo = true; camara.launch(null) }
        catch(e: Exception) { leyendo = false; error("No se pudo abrir la cámara. Revisa el permiso o ingresa manualmente.") }
    }
    val permiso = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { concedido ->
        if(concedido) abrir() else error("Permite el acceso a la cámara o ingresa los valores manualmente.")
    }
    BotonSecundario(
        texto = if(leyendo) "Leyendo comprobante…" else "Escanear comprobante",
        icono = Icons.Filled.DocumentScanner, habilitado = !leyendo,
        onClick = {
            if(ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) abrir()
            else permiso.launch(Manifest.permission.CAMERA)
        },
    )
}

