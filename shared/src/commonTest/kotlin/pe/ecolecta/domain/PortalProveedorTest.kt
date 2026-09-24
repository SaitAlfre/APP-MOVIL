package pe.ecolecta.domain

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.*
import pe.ecolecta.presentation.proveedor.PortalProveedorState
import kotlin.test.*
import kotlin.time.Instant

class PortalProveedorTest {
    @Test fun semanaComienzaJuevesYTerminaMiercolesInclusoCambioDeAno() {
        assertEquals(LocalDate(2026, 9, 17), inicioSemanaProveedor(LocalDate(2026, 9, 17)))
        assertEquals(LocalDate(2026, 9, 17), inicioSemanaProveedor(LocalDate(2026, 9, 23)))
        assertEquals(LocalDate(2026, 9, 24), inicioSemanaProveedor(LocalDate(2026, 9, 24)))
        assertEquals(LocalDate(2020, 12, 31), inicioSemanaProveedor(LocalDate(2021, 1, 1)))
    }

    @Test fun rechazaNumerosNoFinitosNegativosExcesoDecimalesYDatosIncompletos() {
        for(valor in listOf("NaN", "Infinity", "-1", "0", "hola", "1.001", "18500.01")) {
            assertFailsWith<IllegalArgumentException> { validarReclamo(valor, "Litros mal registrados", "La cantidad es incorrecta") }
        }
        assertFailsWith<IllegalArgumentException> { validarReclamo("12", "", "La cantidad es incorrecta") }
        assertFailsWith<IllegalArgumentException> { validarReclamo("12", "Litros mal registrados", "Corto") }
        assertEquals(12.75, validarReclamo("12,75", "Entrega no registrada", "Falta la entrega del jueves"))
    }

    @Test fun resumenExcluyeAnuladasYFuturasYRespetaMedianocheLima() {
        fun entrega(id: String, fecha: String, anulada: Boolean = false) = Entrega.crear(
            id, "j", "p", "u", "z", "v", 10.0, 1, null, Instant.parse(fecha).toEpochMilliseconds(), "d", null,
        ).getOrThrow().copy(anulada = anulada)
        val s = PortalProveedorState(hoy = LocalDate(2026, 9, 17), entregas = listOf(
            entrega("miercoles", "2026-09-17T04:59:00Z"),
            entrega("jueves", "2026-09-17T05:00:00Z"),
            entrega("anulada", "2026-09-17T09:00:00Z", true),
            entrega("siguienteSemana", "2026-09-24T05:00:00Z"),
        ))
        assertEquals(10.0, s.litrosHoy)
        assertEquals(10.0, s.litrosSemana)
        assertNull(s.ultimaLiquidacion)
    }

    private fun pago(id: String, desde: String, hasta: String, estado: String, precio: Double = 1.8, litros: Double = 100.0) =
        PagoProveedor(id, "p", desde, hasta, litros, precio, litros * precio, 0.0, litros * precio, estado)

    /** Bug reportado: "Mis pagos" mostraba un pago publicado e Inicio decía "Por confirmar". */
    @Test fun inicioUsaLaUltimaLiquidacionPublicadaAunqueSeaDeUnaSemanaYaCerrada() {
        val pagada = pago("liq-2026-09-10-p", "2026-09-10", "2026-09-16", "PAGADA", precio = 1.75)
        val s = PortalProveedorState(hoy = LocalDate(2026, 9, 24), pagos = pagosVisibles(listOf(pagada)))

        // La semana en curso (24–30) nunca tiene liquidación: antes se buscaba solo esa.
        assertEquals(pagada, s.ultimaLiquidacion)
        assertEquals(s.pagos.first(), s.ultimaLiquidacion, "Inicio resume la misma liquidación que encabeza Mis pagos")
        assertEquals(1.75, s.ultimaLiquidacion!!.precio)
        assertEquals(175.0, s.ultimaLiquidacion!!.total)
    }

    /** El panel web solo tiene `pendiente` (calculada, sin pagar) y `pagada`: se muestran tal cual. */
    @Test fun unaLiquidacionPendienteDelPanelSeMuestraComoPendienteDePagoNuncaComoPagadaNiAprobada() {
        val pagada = pago("${PREFIJO_PAGO_SERVIDOR}1", "2026-09-10", "2026-09-16", "PAGADA", precio = 1.5)
        val pendiente = pago("${PREFIJO_PAGO_SERVIDOR}2", "2026-09-17", "2026-09-23", "PENDIENTE", precio = 1.6)
        val s = PortalProveedorState(pagos = pagosVisibles(listOf(pagada, pendiente)))

        assertEquals(listOf(pendiente, pagada), s.pagos, "Mis pagos: más reciente primero")
        assertEquals(pendiente, s.ultimaLiquidacion, "Inicio resume la misma que encabeza Mis pagos")
        assertEquals("Pendiente de pago", pe.ecolecta.presentation.proveedor.etiquetaPago(s.ultimaLiquidacion!!.estado))
        assertEquals("Pagado", pe.ecolecta.presentation.proveedor.etiquetaPago(pagada.estado))
        assertNull(pendiente.fechaPago)
    }

    @Test fun noPresentaImportesDeLiquidacionesNoPublicadasNiInventaPrecios() {
        val borrador = pago("b", "2026-09-17", "2026-09-23", "BORRADOR")
        val anulada = pago("a", "2026-09-17", "2026-09-23", "ANULADA")
        val enRevision = pago("r", "2026-09-17", "2026-09-23", "EN_REVISION")
        assertNull(PortalProveedorState(pagos = listOf(borrador, anulada, enRevision)).ultimaLiquidacion)

        val aprobada = pago("ok", "2026-09-10", "2026-09-16", "APROBADA")
        val s = PortalProveedorState(pagos = pagosVisibles(listOf(borrador, anulada, aprobada)))
        assertEquals("ok", s.ultimaLiquidacion!!.id, "la más reciente PUBLICADA, no el borrador más nuevo")
        assertEquals("Aprobado", pe.ecolecta.presentation.proveedor.etiquetaPago(s.ultimaLiquidacion!!.estado), "aprobada no se muestra como pagada")
    }

    @Test fun laLiquidacionDelPanelWebReemplazaALaLocalDelMismoPeriodoSinTocarLasDemas() {
        val local = pago("liq-2026-09-10-p", "2026-09-10", "2026-09-16", "APROBADA", precio = 1.5)
        val delPanel = pago("${PREFIJO_PAGO_SERVIDOR}7", "2026-09-10", "2026-09-16", "PAGADA", precio = 1.6)
        val historica = pago("liq-2026-09-03-p", "2026-09-03", "2026-09-09", "PAGADA", precio = 1.4)

        val visibles = pagosVisibles(listOf(historica, local, delPanel))

        assertEquals(listOf(delPanel, historica), visibles)
        assertEquals(delPanel, ultimaLiquidacionEmitida(listOf(historica, local, delPanel)))
    }
}
