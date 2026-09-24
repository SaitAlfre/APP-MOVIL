package pe.ecolecta.domain

import com.google.zxing.BarcodeFormat
import com.google.zxing.BinaryBitmap
import com.google.zxing.EncodeHintType
import com.google.zxing.RGBLuminanceSource
import com.google.zxing.common.HybridBinarizer
import com.google.zxing.qrcode.QRCodeReader
import com.google.zxing.qrcode.QRCodeWriter
import java.awt.image.BufferedImage
import java.io.File
import javax.imageio.ImageIO
import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * El contenido del QR del proveedor, guardado como PNG (lo que hacen "Descargar" y "Compartir"), se
 * vuelve a leer con un decodificador independiente y resuelve la misma ficha. Usa ZXing, el mismo
 * motor que qr-kit usa en Android.
 */
class QrEscaneableTest {
    @Test
    fun `el QR guardado como PNG se puede abrir y escanear y devuelve el codigo del proveedor`() {
        val contenido = generarQrProveedor("PRV-FAON-01")
        val matriz = QRCodeWriter().encode(contenido, BarcodeFormat.QR_CODE, 400, 400, mapOf(EncodeHintType.MARGIN to 2))
        val imagen = BufferedImage(matriz.width, matriz.height, BufferedImage.TYPE_INT_RGB)
        for (x in 0 until matriz.width) for (y in 0 until matriz.height) imagen.setRGB(x, y, if (matriz[x, y]) 0x000000 else 0xFFFFFF)
        val png = File.createTempFile("qr-ecolecta-PRV-FAON-01", ".png")
        try {
            ImageIO.write(imagen, "png", png)

            val abierta = ImageIO.read(png)
            val pixeles = IntArray(abierta.width * abierta.height).also { abierta.getRGB(0, 0, abierta.width, abierta.height, it, 0, abierta.width) }
            val leido = QRCodeReader().decode(BinaryBitmap(HybridBinarizer(RGBLuminanceSource(abierta.width, abierta.height, pixeles)))).text

            assertEquals(contenido, leido)
            assertEquals(ReferenciaQrProveedor.PorCodigo("PRV-FAON-01"), leerQrProveedor(leido))
        } finally {
            png.delete()
        }
    }
}
