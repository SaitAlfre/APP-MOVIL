package pe.ecolecta.domain

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

class ProveedorQrTest {
    @Test
    fun `genera y lee el mismo codigo de proveedor`() {
        val contenido = generarQrProveedor("PRV-FAON-01")

        assertEquals(ReferenciaQrProveedor.PorCodigo("PRV-FAON-01"), leerQrProveedor(contenido))
    }

    @Test
    fun `solo incluye el codigo, con el mismo formato que el panel web`() {
        assertEquals("ECOLECTA:PROVEEDOR:CODIGO:PRV-FAON-01", generarQrProveedor("PRV-FAON-01"))
    }

    @Test
    fun `tolera espacios alrededor del contenido escaneado`() {
        assertEquals(ReferenciaQrProveedor.PorCodigo("PRV-001"), leerQrProveedor("  ${generarQrProveedor("PRV-001")}  "))
    }

    @Test
    fun `sigue leyendo QR antiguos con id`() {
        assertEquals(ReferenciaQrProveedor.PorIdAntiguo("p-123"), leerQrProveedor("ECOLECTA:PROVEEDOR:p-123"))
    }

    @Test
    fun `retorna null si el contenido no tiene el prefijo de Ecolecta`() {
        assertNull(leerQrProveedor("https://ejemplo.com/otro-qr"))
    }

    @Test
    fun `retorna null si el prefijo esta pero el valor esta vacio`() {
        assertNull(leerQrProveedor("ECOLECTA:PROVEEDOR:"))
        assertNull(leerQrProveedor("ECOLECTA:PROVEEDOR:CODIGO:"))
    }
}
