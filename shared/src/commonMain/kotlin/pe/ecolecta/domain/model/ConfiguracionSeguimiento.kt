package pe.ecolecta.domain.model

/** Parámetros de captura de ubicación durante el seguimiento del ACOPIADOR. */
object ConfiguracionSeguimiento {
    /** Intervalo objetivo entre capturas periódicas (además del fix inicial inmediato al iniciar). */
    const val INTERVALO_MS = 15_000L
    const val INTERVALO_MINIMO_MS = 10_000L
    const val DISTANCIA_MINIMA_M = 5f
}
