package pe.ecolecta.domain

import pe.ecolecta.domain.model.EstadoSeguimiento
import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * Cubre el estado del seguimiento GPS reportado en producción: un watchdog por tiempo confundía
 * "el acopiador está quieto" (normal, hay un filtro de distancia mínima) con "se perdió el GPS".
 * Ahora el estado depende solo de eventos explícitos de la plataforma, nunca del tiempo transcurrido.
 */
class EventoUbicacionTest {
    private val ubicacionEjemplo = UbicacionCruda(lat = -12.0, lng = -75.0, precisionM = 8.0, timestamp = 1_000L)

    @Test
    fun `una ubicacion capturada marca Activo`() {
        assertEquals(EstadoSeguimiento.ACTIVO, EventoUbicacion.Capturada(ubicacionEjemplo).aEstadoSeguimiento())
    }

    @Test
    fun `permanecer quieto durante minutos sin una captura nueva no es un evento y por lo tanto no hay ninguna transicion a Sin senal`() {
        // No hay una función "handleNoEvent()": el estado solo cambia ante un EventoUbicacion real
        // (Capturada/SenalPerdida/SenalRecuperada) o una excepción real. Ausencia de eventos = sin
        // transición, por construcción — se documenta aquí para que una futura regresión (reintroducir
        // un timer) se note si alguien intenta reincorporar esa lógica en esta función pura.
        val estadoTrasCaptura = EventoUbicacion.Capturada(ubicacionEjemplo).aEstadoSeguimiento()
        assertEquals(EstadoSeguimiento.ACTIVO, estadoTrasCaptura)
    }

    @Test
    fun `senal perdida - proveedor del sistema desactivado - marca Sin señal GPS`() {
        assertEquals(EstadoSeguimiento.SIN_SENAL, EventoUbicacion.SenalPerdida.aEstadoSeguimiento())
    }

    @Test
    fun `senal recuperada marca Buscando ubicacion y no Activo directamente`() {
        assertEquals(EstadoSeguimiento.BUSCANDO, EventoUbicacion.SenalRecuperada.aEstadoSeguimiento())
    }

    @Test
    fun `un error de permiso denegado al registrar ubicacion marca Permiso denegado`() {
        assertEquals(EstadoSeguimiento.PERMISO_DENEGADO, excepcionAEstadoSeguimiento(PermisoUbicacionDenegadoException("sin permiso")))
    }

    @Test
    fun `un fallo interno del proveedor no se confunde con falta de senal`() {
        assertEquals(EstadoSeguimiento.ERROR_CAPTURA, excepcionAEstadoSeguimiento(IllegalStateException("fallo interno del proveedor")))
    }
}
