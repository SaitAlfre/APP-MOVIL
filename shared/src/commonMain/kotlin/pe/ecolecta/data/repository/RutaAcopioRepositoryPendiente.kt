package pe.ecolecta.data.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.EventoRuta
import pe.ecolecta.domain.repository.RutaAcopioRepository

/**
 * Sin backend remoto en esta plataforma (hoy: iOS — la integración real de Firestore es Android-only,
 * ver el plan del Grupo 5): siempre reporta [EventoRuta.NoConectado] y nunca publica nada, sin datos
 * ficticios ni ubicaciones de prueba.
 */
class RutaAcopioRepositoryPendiente : RutaAcopioRepository {
    override fun observar(zonaId: String): Flow<EventoRuta> = flowOf(EventoRuta.NoConectado)

    override suspend fun publicarPosicion(ubicacion: UbicacionAcopiador): Result<Unit> =
        Result.failure(UnsupportedOperationException("Seguimiento remoto disponible solo en Android en esta versión."))

    override suspend fun publicarEstado(
        zonaId: String,
        jornadaId: String,
        jornadaAbiertaEn: Long,
        secuenciaEn: Long,
        seguimientoActivo: Boolean,
        jornadaAbierta: Boolean?,
    ): Result<Unit> =
        Result.failure(UnsupportedOperationException("Seguimiento remoto disponible solo en Android en esta versión."))
}
