package pe.ecolecta.domain.usecase.seguimiento

import pe.ecolecta.domain.model.ContextoPublicacionRuta
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.RutaAcopioRepository
import pe.ecolecta.domain.repository.UbicacionAcopiadorLocalRepository

/**
 * Publica una posición capturada en Firestore. Independiente del guardado local: un fallo aquí nunca
 * debe impedir la siguiente captura (§ Grupo 5, punto 3) — por eso el llamador (el servicio de
 * seguimiento) la invoca en su propio job, sin esperarla de forma bloqueante para seguir capturando.
 */
class PublicarUbicacionRemotaUseCase(
    private val rutaAcopioRepository: RutaAcopioRepository,
    private val ubicacionAcopiadorLocalRepository: UbicacionAcopiadorLocalRepository,
) {
    suspend operator fun invoke(
        contexto: ContextoPublicacionRuta,
        lat: Double,
        lng: Double,
        precisionM: Double,
        capturadaEn: Long,
    ): Result<Unit> {
        val ubicacion = UbicacionAcopiador(
            zonaId = contexto.zonaId,
            zonaNombre = contexto.zonaNombre,
            acopiadorId = contexto.acopiadorId,
            acopiadorNombre = contexto.acopiadorNombre,
            vehiculoId = contexto.vehiculoId,
            vehiculoNombre = contexto.vehiculoNombre,
            jornadaId = contexto.jornadaId,
            jornadaAbiertaEn = contexto.jornadaAbiertaEn,
            fecha = contexto.fecha,
            lat = lat,
            lng = lng,
            precisionM = precisionM,
            capturadaEn = capturadaEn,
            // Una posición capturada mientras el servicio corre siempre implica ambos en true: si no
            // lo estuvieran, este código no se estaría ejecutando (ver DetenerSeguimientoUseCase).
            secuenciaEn = capturadaEn,
            seguimientoActivo = true,
            jornadaAbierta = true,
        )
        val resultado = rutaAcopioRepository.publicarPosicion(ubicacion)
        if (resultado.isSuccess) {
            ubicacionAcopiadorLocalRepository.marcarPublicada(contexto.acopiadorId)
        }
        return resultado
    }
}
