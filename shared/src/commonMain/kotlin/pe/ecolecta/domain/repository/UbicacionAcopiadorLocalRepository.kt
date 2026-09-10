package pe.ecolecta.domain.repository

/** Último fix GPS capturado localmente por el propio ACOPIADOR (caché offline, no historial). */
data class UbicacionLocalAcopiador(
    val usuarioId: String,
    val jornadaId: String,
    val zonaId: String,
    val lat: Double,
    val lng: Double,
    val precisionM: Double,
    val capturadaEn: Long,
    val publicada: Boolean,
)

interface UbicacionAcopiadorLocalRepository {
    suspend fun guardar(
        usuarioId: String,
        jornadaId: String,
        zonaId: String,
        lat: Double,
        lng: Double,
        precisionM: Double,
        capturadaEn: Long,
        publicada: Boolean,
    )

    suspend fun marcarPublicada(usuarioId: String)
    suspend fun obtener(usuarioId: String): UbicacionLocalAcopiador?
    suspend fun eliminar(usuarioId: String)
}
