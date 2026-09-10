package pe.ecolecta.domain.usecase.seguimiento

import kotlinx.coroutines.withTimeoutOrNull
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.AvisoRemotoPendiente
import pe.ecolecta.domain.repository.AvisoRemotoPendienteRepository
import pe.ecolecta.domain.repository.RutaAcopioRepository

/**
 * Publica un cambio de estado (detener seguimiento o cerrar jornada) sin tocar la posición ni
 * `capturadaEn`. Se intenta de inmediato con un tiempo límite corto; si falla o no hay conexión, el
 * aviso se guarda localmente para reintentarlo cuando la app vuelva a ejecutarse con conexión
 * ([ReintentarAvisoPendienteUseCase]) — sin garantía de reintento con la app completamente cerrada.
 */
class PublicarEstadoRemotoUseCase(
    private val rutaAcopioRepository: RutaAcopioRepository,
    private val avisoRemotoPendienteRepository: AvisoRemotoPendienteRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(
        usuarioId: String,
        zonaId: String,
        jornadaId: String,
        jornadaAbiertaEn: Long,
        jornadaAbierta: Boolean?,
    ) {
        val secuenciaEn = reloj.ahora().toEpochMilliseconds()
        val resultado = withTimeoutOrNull(TIMEOUT_MS) {
            rutaAcopioRepository.publicarEstado(
                zonaId = zonaId,
                jornadaId = jornadaId,
                jornadaAbiertaEn = jornadaAbiertaEn,
                secuenciaEn = secuenciaEn,
                seguimientoActivo = false,
                jornadaAbierta = jornadaAbierta,
            )
        }
        if (resultado == null || resultado.isFailure) {
            avisoRemotoPendienteRepository.guardar(
                AvisoRemotoPendiente(
                    usuarioId = usuarioId,
                    zonaId = zonaId,
                    jornadaId = jornadaId,
                    jornadaAbiertaEn = jornadaAbiertaEn,
                    secuenciaEn = secuenciaEn,
                    jornadaAbierta = jornadaAbierta,
                    creadoEn = secuenciaEn,
                ),
            )
        }
    }

    private companion object {
        const val TIMEOUT_MS = 5_000L
    }
}
