package pe.ecolecta.domain.fake

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.Reloj
import kotlin.time.Instant

class FakeReloj(
    private val fecha: LocalDate = LocalDate(2026, 1, 15),
    private val instante: Instant = Instant.fromEpochMilliseconds(1_700_000_000_000L),
) : Reloj {
    override fun hoy(): LocalDate = fecha
    override fun ahora(): Instant = instante
}
