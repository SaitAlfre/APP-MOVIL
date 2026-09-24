package pe.ecolecta.domain

/**
 * Parámetros de la regla de jornadas. Se inyecta por Koin (ver `di/Koin.kt`), así el plazo se cambia
 * en un solo lugar sin tocar el caso de uso. No hay todavía una pantalla ni sincronización remota
 * para editarlo: es configuración de la app.
 *
 * @property plazoReaperturaMinutos minutos desde el cierre en los que el propio acopiador puede
 *   reabrir su jornada del día indicando un motivo. Pasado el plazo, la reapertura exige que un
 *   ADMIN introduzca su usuario y PIN en el mismo teléfono.
 */
data class ConfiguracionJornada(
    val plazoReaperturaMinutos: Int = 30,
)
