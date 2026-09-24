package pe.ecolecta.domain

/** Motivos por los que [pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase] rechaza reabrir. */
sealed class ReaperturaJornadaException(mensaje: String) : Exception(mensaje) {
    data object MotivoObligatorio :
        ReaperturaJornadaException("Explica por qué reabres la jornada (al menos 10 caracteres).")

    data object NoEncontrada : ReaperturaJornadaException("La jornada no existe.")

    data object NoPertenece : ReaperturaJornadaException("Solo puedes reabrir tu propia jornada.")

    data object YaAbierta : ReaperturaJornadaException("La jornada ya está abierta.")

    data object NoEsDeHoy :
        ReaperturaJornadaException("Solo se puede reabrir la jornada de hoy. Las de días anteriores quedan cerradas.")

    data object OtraJornadaAbierta :
        ReaperturaJornadaException("Ya tienes otra jornada abierta. Ciérrala antes de reabrir esta.")

    /** Pasó el plazo configurado: hace falta que un administrador autorice en este mismo teléfono. */
    data class FueraDePlazo(val plazoMinutos: Int) : ReaperturaJornadaException(
        "Pasaron más de $plazoMinutos minutos desde el cierre. Un administrador debe autorizar la reapertura " +
            "en este teléfono con su usuario y PIN.",
    )

    data object AutorizacionInvalida : ReaperturaJornadaException(
        "La autorización no es válida: se necesita el usuario y PIN de un administrador activo distinto de ti.",
    )

    data object RegistroFallido :
        ReaperturaJornadaException("No se pudo registrar la reapertura. La jornada sigue cerrada; inténtalo de nuevo.")
}
