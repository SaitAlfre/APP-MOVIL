package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.repository.EventoRuta
import pe.ecolecta.domain.repository.RutaAcopioRepository

class FakeRutaAcopioRepository : RutaAcopioRepository {
    private val eventos = MutableStateFlow<EventoRuta>(EventoRuta.NoConectado)
    var fallarPublicaciones: Boolean = false

    var ultimaPosicionPublicada: UbicacionAcopiador? = null
        private set
    var ultimoEstadoPublicado: EstadoPublicado? = null
        private set

    data class EstadoPublicado(
        val zonaId: String,
        val jornadaId: String,
        val jornadaAbiertaEn: Long,
        val secuenciaEn: Long,
        val seguimientoActivo: Boolean,
        val jornadaAbierta: Boolean?,
    )

    fun emitir(evento: EventoRuta) {
        eventos.value = evento
    }

    override fun observar(zonaId: String): Flow<EventoRuta> = eventos.asStateFlow()

    override suspend fun publicarPosicion(ubicacion: UbicacionAcopiador): Result<Unit> {
        if (fallarPublicaciones) return Result.failure(IllegalStateException("fallo simulado"))
        ultimaPosicionPublicada = ubicacion
        return Result.success(Unit)
    }

    override suspend fun publicarEstado(
        zonaId: String,
        jornadaId: String,
        jornadaAbiertaEn: Long,
        secuenciaEn: Long,
        seguimientoActivo: Boolean,
        jornadaAbierta: Boolean?,
    ): Result<Unit> {
        if (fallarPublicaciones) return Result.failure(IllegalStateException("fallo simulado"))
        ultimoEstadoPublicado = EstadoPublicado(zonaId, jornadaId, jornadaAbiertaEn, secuenciaEn, seguimientoActivo, jornadaAbierta)
        return Result.success(Unit)
    }
}
