package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.ZonaOcupadaException
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

/**
 * Una jornada abierta por usuario y día (§20): si ya existe una para hoy, se retoma en vez de crear otra.
 * Una zona solo admite una jornada abierta a la vez: es la asignación explícita acopiador↔zona que permite
 * al PROVEEDOR identificar sin ambigüedad a su acopiador (ver ZonaOcupadaException).
 */
class AbrirJornadaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(usuarioId: String, zonaId: String, vehiculoId: String): Result<Jornada> = runCatching {
        val hoy = reloj.hoy()
        val jornada = jornadaRepository.obtenerPorUsuarioYFecha(usuarioId, hoy) ?: run {
            val nueva = Jornada(
                id = nuevoId(),
                usuarioId = usuarioId,
                zonaId = zonaId,
                vehiculoId = vehiculoId,
                fecha = hoy,
                abiertaEn = reloj.ahora().toEpochMilliseconds(),
                cerradaEn = null,
                syncState = SyncState.PENDING,
            )
            // insertarSiZonaLibre comprueba la zona e inserta en una sola operación atómica: evita que
            // dos llamadas solapadas (p. ej. doble tap) abran dos jornadas en la misma zona.
            val ocupante = jornadaRepository.insertarSiZonaLibre(nueva)
            if (ocupante != null) throw ZonaOcupadaException(zonaId)
            nueva
        }
        jornadaEnCursoRepository.establecer(jornada)
        jornada
    }
}
