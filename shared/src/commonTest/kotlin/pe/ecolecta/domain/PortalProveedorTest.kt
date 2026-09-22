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
        assertNull(s.pagoSemana)
    }
}
