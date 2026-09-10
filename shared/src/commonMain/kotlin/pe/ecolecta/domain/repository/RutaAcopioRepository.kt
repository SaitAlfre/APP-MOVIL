package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.UbicacionAcopiador

/** Evento crudo de la fuente remota de ubicación del acopiador (Firestore, cuando esté conectado). */
sealed interface EventoRuta {
    data class Recibida(val ubicacion: UbicacionAcopiador) : EventoRuta

    /**
     * El documento remoto de la zona todavía no existe: nunca se publicó una posición para ninguna
     * jornada de esa zona. Distinto de [NoConectado] (que es un error real) — este es el estado
     * normal "todavía no hay nada que mostrar", igual que si la jornada no hubiera empezado.
     */
    data object SinDatos : EventoRuta

    /**
     * El backend remoto no está disponible por una razón distinta a la conexión a internet del
     * dispositivo (vínculo sin configurar en Firebase Console, plataforma sin integración — ver
     * [pe.ecolecta.data.repository.RutaAcopioRepositoryPendiente] —, u otro error del servidor). A
     * propósito NO es lo mismo que [SinConexion], para no confundir al proveedor.
     */
    data object NoConectado : EventoRuta

    /** Sin conexión a internet del dispositivo (distinto de [NoConectado]: ver arriba). */
    data object SinConexion : EventoRuta
}

/**
 * Fuente remota de la ubicación del acopiador de una zona (documento `rutas_activas/{zonaId}`).
 * Sin backend real todavía en algunas plataformas: ver [pe.ecolecta.data.repository.RutaAcopioRepositoryPendiente].
 */
interface RutaAcopioRepository {
    fun observar(zonaId: String): Flow<EventoRuta>

    /** Publica una posición completa (siempre implica seguimientoActivo=true, jornadaAbierta=true). */
    suspend fun publicarPosicion(ubicacion: UbicacionAcopiador): Result<Unit>

    /**
     * Publica solo el estado (detener seguimiento o cerrar jornada), sin tocar la posición. Nunca
     * modifica `capturadaEn`. [jornadaAbierta] en `null` significa "no tocar ese campo" (caso
     * "detener seguimiento", que nunca debe finalizar la jornada); en `false` significa "cerrar
     * jornada" (el único caso permitido para finalizarla).
     */
    suspend fun publicarEstado(
        zonaId: String,
        jornadaId: String,
        jornadaAbiertaEn: Long,
        secuenciaEn: Long,
        seguimientoActivo: Boolean,
        jornadaAbierta: Boolean?,
    ): Result<Unit>
}
