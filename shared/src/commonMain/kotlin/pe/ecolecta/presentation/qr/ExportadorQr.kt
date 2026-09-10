package pe.ecolecta.presentation.qr

import androidx.compose.ui.graphics.ImageBitmap

/**
 * Guardar e imprimir la tarjeta de QR usando la funcionalidad nativa de cada plataforma. Compartir
 * no necesita una implementación propia: se resuelve con `qrgenerator.shareQrCodeImage` (qr-kit),
 * multiplataforma de fábrica.
 */
expect class ExportadorQr {
    suspend fun guardarEnGaleria(bitmap: ImageBitmap, nombreArchivo: String): Result<Unit>
    fun imprimir(bitmap: ImageBitmap, tituloTrabajo: String)
}
