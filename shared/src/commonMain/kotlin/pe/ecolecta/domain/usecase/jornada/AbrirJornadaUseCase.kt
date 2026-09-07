package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

/** Una jornada abierta por usuario y día (§20): si ya existe una para hoy, se retoma en vez de crear otra. */
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
            jornadaRepository.insertar(nueva)
            nueva
        }
        jornadaEnCursoRepository.establecer(jornada)
        jornada
    }
}
