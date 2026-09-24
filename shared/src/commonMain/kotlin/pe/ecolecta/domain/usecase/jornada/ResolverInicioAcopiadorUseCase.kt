package pe.ecolecta.domain.usecase.jornada

import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ZonaRepository

/** Qué debe ver el ACOPIADOR al entrar (login o reapertura de la app con la sesión guardada). */
sealed interface InicioAcopiador {
    /** Tiene una jornada abierta (de hoy o una olvidada de otro día): se reanuda. */
    data class JornadaAbierta(val jornada: Jornada) : InicioAcopiador

    /** Ya cerró la jornada de hoy: una por día, así que no se ofrece abrir otra. */
    data class JornadaTerminadaHoy(val jornada: Jornada) : InicioAcopiador

    /** No hay ninguna zona activa en la que abrir una jornada. */
    data object SinZonaActiva : InicioAcopiador

    /** Puede elegir zona y vehículo para abrir la jornada de hoy. */
    data object SeleccionarZonaVehiculo : InicioAcopiador
}

/**
 * Antes App.kt solo preguntaba "¿hay una jornada abierta?" y, si no, mandaba siempre a la selección
 * de zona/vehículo: una jornada ya cerrada hoy terminaba en una pantalla genérica cuyo "Abrir
 * jornada" solo podía fallar, y sin zonas activas el formulario quedaba vacío y deshabilitado sin
 * explicar por qué. El orden importa: una jornada abierta siempre gana; luego la del día cerrada.
 */
class ResolverInicioAcopiadorUseCase(
    private val reanudarJornadaSiExisteUseCase: ReanudarJornadaSiExisteUseCase,
    private val jornadaRepository: JornadaRepository,
    private val zonaRepository: ZonaRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(usuarioId: String): InicioAcopiador {
        reanudarJornadaSiExisteUseCase(usuarioId)?.let { return InicioAcopiador.JornadaAbierta(it) }
        jornadaRepository.obtenerPorUsuarioYFecha(usuarioId, reloj.hoy())
            ?.takeIf { !it.estaAbierta }
            ?.let { return InicioAcopiador.JornadaTerminadaHoy(it) }
        if (zonaRepository.observarActivas().first().isEmpty()) return InicioAcopiador.SinZonaActiva
        return InicioAcopiador.SeleccionarZonaVehiculo
    }
}

/** La jornada de hoy del usuario si ya está cerrada; la usa el inicio para mostrar su resumen. */
class ObtenerJornadaTerminadaHoyUseCase(
    private val jornadaRepository: JornadaRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(usuarioId: String): Jornada? =
        jornadaRepository.obtenerPorUsuarioYFecha(usuarioId, reloj.hoy())?.takeIf { !it.estaAbierta }
}
