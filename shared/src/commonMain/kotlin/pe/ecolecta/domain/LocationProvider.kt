package pe.ecolecta.domain

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.EstadoSeguimiento

data class UbicacionCruda(
    val lat: Double,
    val lng: Double,
    val precisionM: Double,
    val timestamp: Long,
)

/**
 * Eventos que puede emitir la captura de GPS. La ausencia de una captura nueva NO es una señal de
 * pérdida de GPS (el filtro de distancia mínima hace que, si el acopiador está quieto, sea normal no
 * recibir actualizaciones) — [SenalPerdida]/[SenalRecuperada] solo se emiten cuando la plataforma
 * reporta explícitamente que la señal/el proveedor de ubicación dejó de estar disponible o volvió.
 */
sealed interface EventoUbicacion {
    data class Capturada(val ubicacion: UbicacionCruda) : EventoUbicacion
    data object SenalPerdida : EventoUbicacion
    data object SenalRecuperada : EventoUbicacion
}

/**
 * Traduce un evento a estado de UI. Es una función pura (sin reloj, sin temporizador) a propósito:
 * el bug anterior fue justamente un watchdog por tiempo que confundía "quietud" con "sin señal".
 * Solo un evento explícito puede cambiar el estado — la ausencia de eventos no cambia nada.
 */
fun EventoUbicacion.aEstadoSeguimiento(): EstadoSeguimiento = when (this) {
    is EventoUbicacion.Capturada -> EstadoSeguimiento.ACTIVO
    EventoUbicacion.SenalPerdida -> EstadoSeguimiento.SIN_SENAL
    // Recuperar el proveedor no es lo mismo que tener ya una posición real: se espera la próxima
    // captura antes de volver a mostrar "Activo".
    EventoUbicacion.SenalRecuperada -> EstadoSeguimiento.BUSCANDO
}

/**
 * Falla al capturar ubicación por falta de permiso. Tipo propio (no `java.lang.SecurityException`,
 * que no existe en Kotlin/Native) para que la clasificación de errores sea multiplataforma.
 */
class PermisoUbicacionDenegadoException(mensaje: String) : Exception(mensaje)

/** La posición se recibió, pero no pudo persistirse en la base local. Conserva la causa original. */
class GuardadoUbicacionException(causa: Throwable) : Exception("No se pudo guardar la ubicación.", causa)

/** Los fallos de almacenamiento o captura no prueban que la señal GPS esté ausente. */
fun excepcionAEstadoSeguimiento(error: Throwable): EstadoSeguimiento = when (error) {
    is PermisoUbicacionDenegadoException -> EstadoSeguimiento.PERMISO_DENEGADO
    is GuardadoUbicacionException -> EstadoSeguimiento.ERROR_ALMACENAMIENTO
    else -> EstadoSeguimiento.ERROR_CAPTURA
}

/**
 * Captura de GPS específica de cada plataforma. En Android usa el proveedor fusionado de ubicación;
 * en iOS queda como stub en esta versión (alcance Android-only confirmado para esta demo).
 */
expect class LocationProvider {
    fun observarUbicacion(): Flow<EventoUbicacion>
}
