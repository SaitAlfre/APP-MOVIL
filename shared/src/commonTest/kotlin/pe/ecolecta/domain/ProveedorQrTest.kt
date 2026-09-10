package pe.ecolecta.domain

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

class ProveedorQrTest {
    @Test
    fun `genera y extrae el mismo id de proveedor`() {
        val contenido = generarQrProveedor("p-123")

        assertEquals("p-123", extraerProveedorIdDeQr(contenido))
    }

    @Test
    fun `no incluye datos ademas del id en el contenido generado`() {
        val contenido = generarQrProveedor("p-123")

        assertEquals("ECOLECTA:PROVEEDOR:p-123", contenido)
    }

    @Test
    fun `tolera espacios alrededor del contenido escaneado`() {
        val contenido = "  ${generarQrProveedor("p-123")}  "

        assertEquals("p-123", extraerProveedorIdDeQr(contenido))
    }

    @Test
    fun `retorna null si el contenido no tiene el prefijo de Ecolecta`() {
        assertNull(extraerProveedorIdDeQr("https://ejemplo.com/otro-qr"))
    }

    @Test
    fun `retorna null si el prefijo esta pero el id esta vacio`() {
        assertNull(extraerProveedorIdDeQr("ECOLECTA:PROVEEDOR:"))
    }
}
