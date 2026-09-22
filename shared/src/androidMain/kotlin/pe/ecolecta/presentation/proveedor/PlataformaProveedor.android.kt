package pe.ecolecta.presentation.proveedor

import android.app.Activity
import android.content.ContextWrapper
import android.content.Intent
import android.util.Base64
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.runtime.*
import androidx.compose.ui.platform.LocalContext
import kotlinx.coroutines.*
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.unit.dp

@Composable actual fun recordarFotoProveedor(resultado: (String) -> Unit, error: (String) -> Unit): () -> Unit {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val onResult by rememberUpdatedState(resultado)
    val onError by rememberUpdatedState(error)
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        if (uri != null) scope.launch {
            try {
                val encoded = withContext(Dispatchers.IO) {
                    val mime = context.contentResolver.getType(uri)
                    require(mime in listOf("image/jpeg", "image/png", "image/webp")) { "Selecciona una imagen JPG, PNG o WebP." }
                    val bytes = context.contentResolver.openInputStream(uri)?.use { input ->
                        val output = java.io.ByteArrayOutputStream()
                        val buffer = ByteArray(8192)
                        while (true) {
                            val count = input.read(buffer)
                            if (count < 0) break
                            require(output.size() + count <= 4_000_000) { "Selecciona una foto de hasta 4 MB." }
                            output.write(buffer, 0, count)
                        }
                        output.toByteArray()
                    } ?: kotlin.error("No se pudo abrir la fotografía.")
                    val bounds = android.graphics.BitmapFactory.Options().apply { inJustDecodeBounds = true }
                    android.graphics.BitmapFactory.decodeByteArray(bytes, 0, bytes.size, bounds)
                    require(bounds.outWidth > 0 && bounds.outHeight > 0) { "La fotografía no es una imagen válida." }
                    var sample = 1
                    while (maxOf(bounds.outWidth, bounds.outHeight) / sample > 1280) sample *= 2
                    val bitmap = android.graphics.BitmapFactory.decodeByteArray(bytes, 0, bytes.size,
                        android.graphics.BitmapFactory.Options().apply { inSampleSize = sample })
                        ?: kotlin.error("No se pudo leer la fotografía.")
                    try {
                        val output = java.io.ByteArrayOutputStream()
                        bitmap.compress(android.graphics.Bitmap.CompressFormat.JPEG, 80, output)
                        require(output.size() <= 740_000) { "La foto sigue siendo demasiado grande; selecciona una más pequeña." }
                        "data:image/jpeg;base64," + Base64.encodeToString(output.toByteArray(), Base64.NO_WRAP)
                    } finally { bitmap.recycle() }
                }
                onResult(encoded)
            } catch(e: CancellationException) { throw e }
            catch(e: Exception) { onError(e.message ?: "No se pudo adjuntar la foto.") }
        }
    }
    return { launcher.launch("image/*") }
}

@Composable actual fun recordarCompartirProveedor(error: (String) -> Unit): (String) -> Unit {
    val context = LocalContext.current
    return { texto ->
        try { context.startActivity(Intent.createChooser(Intent(Intent.ACTION_SEND).apply {
            type = "text/plain"; putExtra(Intent.EXTRA_TEXT, texto)
        }, "Compartir solicitud")) }
        catch(e: Exception) { error("No se pudo abrir una aplicación para compartir.") }
    }
}

@Composable actual fun recordarGuardarComprobante(resultado: (String) -> Unit): (String, String) -> Unit {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var contenido by remember { mutableStateOf("") }
    val onResult by rememberUpdatedState(resultado)
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.CreateDocument("text/plain")) { uri ->
        if (uri != null) scope.launch {
            try {
                withContext(Dispatchers.IO) {
                    val salida = context.contentResolver.openOutputStream(uri) ?: error("No se pudo abrir el archivo.")
                    salida.use { it.write(contenido.toByteArray(Charsets.UTF_8)) }
                }
                onResult("Comprobante guardado.")
            } catch(e: CancellationException) { throw e }
            catch(e: Exception) { onResult("No se pudo guardar el comprobante: ${e.message}") }
        }
    }
    return { nombre, texto -> contenido = texto; launcher.launch(nombre) }
}

@Composable actual fun BrilloProveedor(aumentado: Boolean) {
    val context = LocalContext.current
    DisposableEffect(context, aumentado) {
        var current = context
        while(current is ContextWrapper && current !is Activity) current = current.baseContext
        val window = (current as? Activity)?.window
        val original = window?.attributes?.screenBrightness
        if (aumentado && window != null) window.attributes = window.attributes.apply { screenBrightness = 1f }
        onDispose { if (window != null && original != null) window.attributes = window.attributes.apply { screenBrightness = original } }
    }
}

@Composable actual fun FotoAdjuntaProveedor(contenido: String) {
    val bitmap = remember(contenido) {
        runCatching {
            val bytes = Base64.decode(contenido.substringAfter("base64,"), Base64.NO_WRAP)
            android.graphics.BitmapFactory.decodeByteArray(bytes, 0, bytes.size)?.asImageBitmap()
        }.getOrNull()
    }
    if(bitmap != null) Image(bitmap, "Fotografía adjunta al reclamo", Modifier.fillMaxWidth().heightIn(max = 220.dp))
    else androidx.compose.material3.Text("No se pudo visualizar la fotografía guardada.")
}
