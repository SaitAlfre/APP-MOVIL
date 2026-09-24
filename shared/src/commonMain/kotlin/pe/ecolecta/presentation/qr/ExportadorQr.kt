package pe.ecolecta.presentation.qr

import androidx.compose.ui.graphics.ImageBitmap

/**
 * Guardar, compartir e imprimir la tarjeta de QR usando la funcionalidad nativa de cada plataforma.
 * Compartir tiene implementación propia: `qrgenerator.shareQrCodeImage` (qr-kit) exige un FileProvider
 * con una autoridad fija y falla con la captura de pantalla de Compose en Android.
 */
expect class ExportadorQr {
    suspend fun guardarEnGaleria(bitmap: ImageBitmap, nombreArchivo: String): Result<Unit>
    suspend fun compartir(bitmap: ImageBitmap, nombreArchivo: String, titulo: String): Result<Unit>
    fun imprimir(bitmap: ImageBitmap, tituloTrabajo: String)
}
