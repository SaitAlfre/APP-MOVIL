package pe.ecolecta.presentation

import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.proveedor.accionesPerfilProveedor
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue

/** El perfil del proveedor ya no lleva al mapa GPS "Mi ruta": lleva a "Mi ciclo". */
class NavegacionProveedorTest {
    @Test
    fun `el perfil del proveedor abre mi ciclo y no el mapa gps antiguo`() {
        val destinos = accionesPerfilProveedor.map { it.second }
        assertTrue(Pantalla.ProveedorMiCiclo in destinos)
        assertTrue(accionesPerfilProveedor.none { (texto, _) -> texto.contains("ruta", ignoreCase = true) || texto.contains("mapa", ignoreCase = true) })
        assertEquals(destinos.distinct(), destinos)
    }
}
