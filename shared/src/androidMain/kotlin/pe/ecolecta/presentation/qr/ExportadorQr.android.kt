package pe.ecolecta.presentation.qr

import android.content.ClipData
import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.graphics.Bitmap
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asAndroidBitmap
import androidx.core.content.FileProvider
import androidx.print.PrintHelper
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import java.io.File
import java.io.OutputStream

actual class ExportadorQr(private val context: Context) {
    actual suspend fun guardarEnGaleria(bitmap: ImageBitmap, nombreArchivo: String): Result<Unit> = withContext(Dispatchers.IO) {
        runCatching {
            val valores = ContentValues().apply {
                put(MediaStore.Images.Media.DISPLAY_NAME, "$nombreArchivo.png")
                put(MediaStore.Images.Media.MIME_TYPE, "image/png")
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                    // Desde Android 10 no hace falta permiso para guardar imágenes propias en Pictures/.
                    put(MediaStore.Images.Media.RELATIVE_PATH, "${Environment.DIRECTORY_PICTURES}/Ecolecta Huata")
                    put(MediaStore.Images.Media.IS_PENDING, 1)
                }
            }
            val resolver = context.contentResolver
            val uri = resolver.insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, valores)
                ?: error("No se pudo crear el archivo en la galería.")
            try {
                resolver.openOutputStream(uri)?.use { escribirPng(bitmap, it) } ?: error("No se pudo abrir el archivo para escritura.")
                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
                    resolver.update(uri, ContentValues().apply { put(MediaStore.Images.Media.IS_PENDING, 0) }, null, null)
                }
            } catch (e: Exception) {
                // No dejar en la galería un archivo vacío o a medio escribir.
                resolver.delete(uri, null, null)
                throw e
            }
        }
    }

    actual suspend fun compartir(bitmap: ImageBitmap, nombreArchivo: String, titulo: String): Result<Unit> = runCatching {
        val archivo = withContext(Dispatchers.IO) {
            // Solo la carpeta cache/qr está expuesta por el FileProvider (res/xml/rutas_compartidas.xml).
            val carpeta = File(context.cacheDir, CARPETA_COMPARTIDA).apply { mkdirs() }
            File(carpeta, "$nombreArchivo.png").also { destino -> destino.outputStream().use { escribirPng(bitmap, it) } }
        }
        val uri = FileProvider.getUriForFile(context, "${context.packageName}$SUFIJO_AUTORIDAD", archivo)
        val envio = Intent(Intent.ACTION_SEND).apply {
            type = "image/png"
            putExtra(Intent.EXTRA_STREAM, uri)
            putExtra(Intent.EXTRA_TITLE, titulo)
            // ClipData + permiso de lectura temporal: la app elegida puede abrir la imagen sin permisos de almacenamiento.
            clipData = ClipData.newRawUri(titulo, uri)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        context.startActivity(
            Intent.createChooser(envio, titulo).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_GRANT_READ_URI_PERMISSION),
        )
    }

    actual fun imprimir(bitmap: ImageBitmap, tituloTrabajo: String) {
        val printHelper = PrintHelper(context)
        printHelper.scaleMode = PrintHelper.SCALE_MODE_FIT
        printHelper.printBitmap(tituloTrabajo, bitmapDeSoftware(bitmap))
    }

    private fun escribirPng(bitmap: ImageBitmap, salida: OutputStream) {
        if (!bitmapDeSoftware(bitmap).compress(Bitmap.CompressFormat.PNG, 100, salida)) error("No se pudo escribir la imagen.")
    }

    /**
     * La captura de Compose (GraphicsLayer.toImageBitmap) puede ser un bitmap de hardware, que no se
     * puede dibujar en un Canvas de software ni leer píxel a píxel: se copia a ARGB_8888 antes de usarlo.
     */
    private fun bitmapDeSoftware(bitmap: ImageBitmap): Bitmap {
        val original = bitmap.asAndroidBitmap()
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O && original.config == Bitmap.Config.HARDWARE) {
            original.copy(Bitmap.Config.ARGB_8888, false) ?: error("No se pudo preparar la imagen del QR.")
        } else {
            original
        }
    }

    companion object {
        /** Debe coincidir con la autoridad declarada en AndroidManifest.xml (`${applicationId}.qr`). */
        const val SUFIJO_AUTORIDAD = ".qr"
        const val CARPETA_COMPARTIDA = "qr"
    }
}
