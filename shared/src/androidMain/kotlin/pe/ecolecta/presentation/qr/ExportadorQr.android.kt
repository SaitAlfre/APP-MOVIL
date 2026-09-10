package pe.ecolecta.presentation.qr

import android.content.ContentValues
import android.content.Context
import android.graphics.Bitmap
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asAndroidBitmap
import androidx.print.PrintHelper
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

actual class ExportadorQr(private val context: Context) {
    actual suspend fun guardarEnGaleria(bitmap: ImageBitmap, nombreArchivo: String): Result<Unit> = withContext(Dispatchers.IO) {
        runCatching {
            val bitmapAndroid = bitmap.asAndroidBitmap()
            val valores = ContentValues().apply {
                put(MediaStore.Images.Media.DISPLAY_NAME, "$nombreArchivo.png")
                put(MediaStore.Images.Media.MIME_TYPE, "image/png")
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                    put(MediaStore.Images.Media.RELATIVE_PATH, "${Environment.DIRECTORY_PICTURES}/Ecolecta Huata")
                }
            }
            val resolver = context.contentResolver
            val uri = resolver.insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, valores)
                ?: error("No se pudo crear el archivo en la galería.")
            resolver.openOutputStream(uri)?.use { salida ->
                if (!bitmapAndroid.compress(Bitmap.CompressFormat.PNG, 100, salida)) {
                    error("No se pudo escribir la imagen.")
                }
            } ?: error("No se pudo abrir el archivo para escritura.")
        }
    }

    actual fun imprimir(bitmap: ImageBitmap, tituloTrabajo: String) {
        val printHelper = PrintHelper(context)
        printHelper.scaleMode = PrintHelper.SCALE_MODE_FIT
        printHelper.printBitmap(tituloTrabajo, bitmap.asAndroidBitmap())
    }
}
