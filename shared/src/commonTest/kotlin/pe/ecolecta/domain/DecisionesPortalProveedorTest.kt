package pe.ecolecta.domain

import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.fake.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.usecase.admin.MarcarLiquidacionPagadaUseCase
import pe.ecolecta.domain.usecase.admin.ResolverSolicitudUseCase
import pe.ecolecta.domain.usecase.traslado.AutorizarTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.CrearTrasladoUseCase
import kotlin.test.*

class DecisionesPortalProveedorTest {
    private val portal = FakeGestionPortalRepository()
    private val reloj = FakeReloj()
    private val dispositivo = FakeDeviceIdProvider()
    private val proveedores = FakeProveedorRepository()
    private val traslados = FakeTrasladoRepository(proveedores)
    private val servidor = FakeServidorWeb()
    private val resolver = ResolverSolicitudUseCase(portal,
        CrearTrasladoUseCase(traslados, proveedores, reloj), AutorizarTrasladoUseCase(traslados, reloj, dispositivo), servidor)

    private fun solicitud(tipo: String = "RECLAMO", referencia: String? = "entrega") = SolicitudProveedor(
        "solicitud", "proveedor", tipo, referencia, 25.0, "Litros mal registrados", "La medida es incorrecta.",
        creadaEn = 1, estado = "PENDIENTE",
    )

    @Test fun decisionConServidorEsPendienteHastaRecibirConfirmacion() = runTest {
        portal.solicitudes.value = listOf(solicitud())
        resolver("solicitud", true, "admin", "Comprobante revisado.").getOrThrow()
        val local = portal.solicitudes.value.single()
        assertEquals("PENDIENTE", local.estado)
        assertNull(local.respuesta)
        assertEquals("ATENDIDA", local.decisionPendiente)
        assertEquals("Comprobante revisado.", local.respuestaPendiente)
    }

    @Test fun trasladoNoMueveZonaAntesDeQueLoAutoriceElServidor() = runTest {
        val proveedor = Proveedor.crear("proveedor", "P1", "Rosa", "12345678", null, null, "origen", updatedAt = 0).getOrThrow()
        proveedores.insertar(proveedor)
        portal.solicitudes.value = listOf(solicitud("TRASLADO", "destino"))
        resolver("solicitud", true, "admin", "Ruta disponible.").getOrThrow()
        assertEquals("origen", proveedores.obtenerPorId("proveedor")!!.zonaId)
        assertEquals("APROBADA", portal.solicitudes.value.single().decisionPendiente)
        assertEquals("PENDIENTE", portal.solicitudes.value.single().estado)
    }

    @Test fun noPermiteResolverSinRespuestaNiAprobarUnaEntregaSinVincular() = runTest {
        portal.solicitudes.value = listOf(solicitud(referencia = null))
        assertTrue(resolver("solicitud", true, "admin", "Revisado.").isFailure)
        assertTrue(resolver("solicitud", false, "admin", " ").isFailure)
        resolver("solicitud", false, "admin", "No corresponde a una entrega registrada.").getOrThrow()
        assertEquals("RECHAZADA", portal.solicitudes.value.single().decisionPendiente)
    }

    @Test fun pagoDelPanelNoSePresentaPagadoAntesDeLaConfirmacion() = runTest {
        val pago = PagoProveedor("web-1", "proveedor", "2026-10-15", "2026-10-21", 25.0, 1.805, 45.13, 0.0, 45.13, "PENDIENTE")
        portal.pagos.value = listOf(pago)
        val pagar = MarcarLiquidacionPagadaUseCase(portal, FakeAuditoriaRepository(), reloj, dispositivo, servidor)
        pagar(LocalDate(2026, 10, 15), "admin").getOrThrow()
        val local = portal.pagos.value.single()
        assertEquals("PENDIENTE", local.estado)
        assertNull(local.fechaPago)
        assertEquals("PAGADA", local.estadoPendiente)
        assertTrue(pagar(LocalDate(2026, 10, 15), "admin").isFailure)
    }
}
