package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.JornadaDelDiaCerradaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

/**
 * Una jornada abierta por usuario y día (§20): si ya existe una para hoy, se retoma en vez de crear otra.
 * Si la de hoy ya está cerrada no se retoma ni se reabre: falla con [JornadaDelDiaCerradaException] —
 * devolverla como éxito dejaba una jornada cerrada como "en curso" y la UI sin forma de continuar.
 * Varios acopiadores pueden compartir zona; cada usuario conserva una sola jornada activa.
 */
class AbrirJornadaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(usuarioId: String, zonaId: String, vehiculoId: String): Result<Jornada> = runCatching {
        val hoy = reloj.hoy()
        val existente = jornadaRepository.obtenerAbiertaPorUsuario(usuarioId)
            ?: jornadaRepository.obtenerPorUsuarioYFecha(usuarioId, hoy)
        if (existente != null && !existente.estaAbierta) throw JornadaDelDiaCerradaException(existente.id)
        val jornada = existente ?: run {
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
            val existenteConcurrente = jornadaRepository.insertarSiUsuarioLibre(nueva)
            if (existenteConcurrente != null && !existenteConcurrente.estaAbierta) {
                throw JornadaDelDiaCerradaException(existenteConcurrente.id)
            }
            existenteConcurrente ?: nueva
        }
        jornadaEnCursoRepository.establecer(jornada)
        jornada
    }
}
