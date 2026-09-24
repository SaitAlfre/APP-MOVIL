package pe.ecolecta.presentation.qr

import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asSkiaBitmap
import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.addressOf
import kotlinx.cinterop.usePinned
import kotlinx.coroutines.suspendCancellableCoroutine
import org.jetbrains.skia.EncodedImageFormat
import org.jetbrains.skia.Image
import platform.Foundation.NSData
import platform.Foundation.create
import platform.Photos.PHAssetChangeRequest
import platform.Photos.PHPhotoLibrary
import platform.UIKit.UIImage
import platform.UIKit.UIPrintInfo
import platform.UIKit.UIPrintInfoOutputType
import platform.UIKit.UIPrintInteractionController
import kotlin.coroutines.resume

@OptIn(ExperimentalForeignApi::class)
private fun ImageBitmap.aUIImage(): UIImage {
    val bytesPng = Image.makeFromBitmap(this.asSkiaBitmap()).encodeToData(EncodedImageFormat.PNG)!!.bytes
    val nsData = bytesPng.usePinned { pineado ->
        NSData.create(bytes = pineado.addressOf(0), length = bytesPng.size.toULong())
    }
    return UIImage(data = nsData) ?: error("No se pudo generar la imagen del QR.")
}

actual class ExportadorQr {
    actual suspend fun guardarEnGaleria(bitmap: ImageBitmap, nombreArchivo: String): Result<Unit> =
        suspendCancellableCoroutine { continuacion ->
            val imagen = runCatching { bitmap.aUIImage() }.getOrElse {
                continuacion.resume(Result.failure(it))
                return@suspendCancellableCoroutine
            }
            PHPhotoLibrary.sharedPhotoLibrary().performChanges(
                changeBlock = { PHAssetChangeRequest.creationRequestForAssetFromImage(imagen) },
                completionHandler = { exito, error ->
                    if (exito) {
                        continuacion.resume(Result.success(Unit))
                    } else {
                        continuacion.resume(Result.failure(Exception(error?.localizedDescription ?: "No se pudo guardar el QR en tus fotos.")))
                    }
                },
            )
        }

    actual suspend fun compartir(bitmap: ImageBitmap, nombreArchivo: String, titulo: String): Result<Unit> =
        runCatching { qrgenerator.shareQrCodeImage(bitmap, nombreArchivo) }

    actual fun imprimir(bitmap: ImageBitmap, tituloTrabajo: String) {
        val imagen = bitmap.aUIImage()
        val controlador = UIPrintInteractionController.sharedPrintController() ?: return
        val info = UIPrintInfo.printInfo()
        info.outputType = UIPrintInfoOutputType.UIPrintInfoOutputPhoto
        info.jobName = tituloTrabajo
        controlador.printInfo = info
        controlador.printingItem = imagen
        controlador.presentAnimated(true, completionHandler = null)
    }
}
