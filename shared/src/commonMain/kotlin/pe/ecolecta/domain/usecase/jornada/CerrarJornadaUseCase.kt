package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

/**
 * Cierra la jornada del acopiador. El resultado depende SOLO del guardado local: si el cierre falla,
 * la jornada queda abierta (§ requisito: "si el cierre falla, conserva la jornada abierta"). Cerrar
 * una jornada ya cerrada (doble toque, pantalla desactualizada) es idempotente y conserva la hora de
 * cierre original.
 *
 * Cada cierre efectivo deja un registro [AccionAuditoria.CERRAR_JORNADA] (hora y dispositivo), de
 * mejor esfuerzo: no poder auditarlo no deshace un cierre ya guardado. Junto con el de
 * [ReabrirJornadaUseCase] permite reconstruir cuándo y dónde se cerró o reabrió una jornada.
 *
 * Tras el cierre, las entregas y los "sin recojo" de la jornada solo se corrigen con las reglas de
 * auditoría vigentes (reapertura controlada o corrección/anulación con motivo).
 */
class CerrarJornadaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
    private val reloj: Reloj,
    private val auditoriaRepository: AuditoriaRepository,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(jornadaId: String): Result<Unit> {
        val ahora = reloj.ahora().toEpochMilliseconds()
        val cierreLocal = runCatching {
            val jornada = jornadaRepository.obtenerPorId(jornadaId) ?: error("La jornada no existe.")
            if (jornada.estaAbierta) jornadaRepository.cerrar(jornadaId, ahora)
            jornadaEnCursoRepository.limpiar()
            jornada
        }
        val jornada = cierreLocal.getOrElse { return Result.failure(it) }

        if (jornada.estaAbierta) {
            runCatching {
                auditoriaRepository.insertar(
                    Auditoria(
                        id = nuevoId(),
                        entidad = "jornada",
                        entidadId = jornada.id,
                        accion = AccionAuditoria.CERRAR_JORNADA,
                        valorAntes = "cerradaEn=null",
                        valorDespues = "cerradaEn=$ahora",
                        motivo = null,
                        usuarioId = jornada.usuarioId,
                        ocurridoEn = ahora,
                        deviceId = deviceIdProvider.obtenerId(),
                        syncState = SyncState.PENDING,
                    ),
                )
            }
        }

        return Result.success(Unit)
    }
}
