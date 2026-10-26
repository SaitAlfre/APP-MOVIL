package pe.ecolecta.domain
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.proveedor.PortalProveedorState
import kotlin.test.*
class TarifaLecheTest {
    private val octubre = TarifaLeche(1, 1.805, "2026-10-01", "2026-10-31")
    private val noviembre = TarifaLeche(2, 2.0, "2026-11-01", "2026-11-30")
    @Test fun precioNoDependeDeUnaLiquidacionYRespetaLosLimitesInclusivos() {
        val precios = PreciosProveedor(listOf(octubre, noviembre), 1000)
        for (dia in listOf("2026-10-01", "2026-10-31")) {
            val estado = PortalProveedorState(hoy = LocalDate.parse(dia), precios = precios)
            assertEquals(1.805, estado.precioVigente?.precio)
            assertNull(estado.ultimaLiquidacion)
        }
        assertEquals(2.0, precios.vigente(LocalDate.parse("2026-11-01"))?.precio)
        assertNull(precios.vigente(LocalDate.parse("2026-12-01")))
    }
    @Test fun datosAmbiguosONoValidosNoSePresentanComoUnPrecioVigente() {
        val hoy = LocalDate.parse("2026-10-26")
        assertNull(PreciosProveedor(listOf(octubre, octubre.copy(id = 3))).vigente(hoy))
        assertNull(PreciosProveedor(listOf(octubre.copy(precio = -1.0))).vigente(hoy))
        assertNull(PreciosProveedor(listOf(octubre.copy(desde = "invalida"))).vigente(hoy))
    }
}
