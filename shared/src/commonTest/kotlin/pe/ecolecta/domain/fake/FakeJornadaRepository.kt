package pe.ecolecta.domain.fake

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaRepository

class FakeJornadaRepository : JornadaRepository {
    private val jornadas = MutableStateFlow<List<Jornada>>(emptyList())
    var fallarAlCerrar: Boolean = false

    override fun observarTodas(): Flow<List<Jornada>> = jornadas.asStateFlow()

    override suspend fun obtenerPorId(id: String): Jornada? = jornadas.value.firstOrNull { it.id == id }

    override suspend fun obtenerPorUsuarioYFecha(usuarioId: String, fecha: LocalDate): Jornada? =
        jornadas.value.firstOrNull { it.usuarioId == usuarioId && it.fecha == fecha }

    override suspend fun obtenerAbiertaPorZona(zonaId: String): Jornada? =
        jornadas.value.firstOrNull { it.zonaId == zonaId && it.estaAbierta }

    override suspend fun obtenerAbiertaPorUsuario(usuarioId: String): Jornada? =
        jornadas.value.filter { it.usuarioId == usuarioId && it.estaAbierta }.minByOrNull { it.abiertaEn }

    override suspend fun filtrar(fecha: LocalDate?, usuarioId: String?, zonaId: String?, vehiculoId: String?): List<Jornada> =
        jornadas.value.filter { j ->
            (fecha == null || j.fecha == fecha) &&
                (usuarioId == null || j.usuarioId == usuarioId) &&
                (zonaId == null || j.zonaId == zonaId) &&
                (vehiculoId == null || j.vehiculoId == vehiculoId)
        }

    override suspend fun contarAbiertas(): Long = jornadas.value.count { it.estaAbierta }.toLong()

    override suspend fun insertar(jornada: Jornada) {
        jornadas.value = jornadas.value + jornada
    }

    override suspend fun insertarSiZonaLibre(jornada: Jornada): Jornada? {
        val ocupante = obtenerAbiertaPorZona(jornada.zonaId)
        if (ocupante != null && ocupante.usuarioId != jornada.usuarioId) return ocupante
        insertar(jornada)
        return null
    }

    override suspend fun cerrar(id: String, cerradaEn: Long) {
        if (fallarAlCerrar) error("Fallo simulado al cerrar la jornada.")
        jornadas.value = jornadas.value.map { if (it.id == id) it.copy(cerradaEn = cerradaEn) else it }
    }

    override suspend fun reabrirSiZonaLibre(id: String): Jornada? {
        val jornada = obtenerPorId(id) ?: error("La jornada no existe.")
        val ocupante = obtenerAbiertaPorZona(jornada.zonaId)
        if (ocupante != null && ocupante.usuarioId != jornada.usuarioId) return ocupante
        jornadas.value = jornadas.value.map { if (it.id == id) it.copy(cerradaEn = null) else it }
        return null
    }
}
