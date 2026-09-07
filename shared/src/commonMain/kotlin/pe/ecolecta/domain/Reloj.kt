package pe.ecolecta.domain

import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.todayIn
import kotlin.time.Clock
import kotlin.time.Instant

/** Abstrae la hora actual del sistema para que el dominio y sus tests no dependan del reloj real. */
interface Reloj {
    fun hoy(): LocalDate
    fun ahora(): Instant
}

class RelojSistema : Reloj {
    override fun hoy(): LocalDate = Clock.System.todayIn(TimeZone.currentSystemDefault())
    override fun ahora(): Instant = Clock.System.now()
}
