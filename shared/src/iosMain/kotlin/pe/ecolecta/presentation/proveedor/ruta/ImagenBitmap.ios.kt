package pe.ecolecta.presentation.proveedor.ruta

import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.toComposeImageBitmap
import org.jetbrains.skia.Image

actual fun ByteArray.aImageBitmap(): ImageBitmap = Image.makeFromEncoded(this).toComposeImageBitmap()
