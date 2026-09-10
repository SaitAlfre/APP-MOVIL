package pe.ecolecta.domain.usecase.seguimiento

import pe.ecolecta.domain.repository.AvisoRemotoPendienteRepository
import pe.ecolecta.domain.repository.RutaAcopioRepository

/**
 * Reintenta el aviso remoto pendiente de un usuario (detener/cerrar que no se pudo publicar). Se
 * llama al reanudar la app (§ Grupo 5, punto 1) — sin garantía de reintento con la app cerrada.
 */
class ReintentarAvisoPendienteUseCase(
    private val avisoRemotoPendienteRepository: AvisoRemotoPendienteRepository,
    private val rutaAcopioRepository: RutaAcopioRepository,
) {
    suspend operator fun invoke(usuarioId: String) {
        val aviso = avisoRemotoPendienteRepository.obtener(usuarioId) ?: return
        val resultado = rutaAcopioRepository.publicarEstado(
            zonaId = aviso.zonaId,
            jornadaId = aviso.jornadaId,
            jornadaAbiertaEn = aviso.jornadaAbiertaEn,
            secuenciaEn = aviso.secuenciaEn,
            seguimientoActivo = false,
            jornadaAbierta = aviso.jornadaAbierta,
        )
        if (resultado.isSuccess) {
            avisoRemotoPendienteRepository.eliminar(usuarioId)
        }
    }
}
