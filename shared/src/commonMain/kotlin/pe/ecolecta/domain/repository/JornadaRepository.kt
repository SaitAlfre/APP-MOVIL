package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Jornada

interface JornadaRepository {
    fun observarTodas(): Flow<List<Jornada>>
    suspend fun obtenerPorId(id: String): Jornada?
    suspend fun obtenerPorUsuarioYFecha(usuarioId: String, fecha: LocalDate): Jornada?
    suspend fun filtrar(fecha: LocalDate?, usuarioId: String?, zonaId: String?, vehiculoId: String?): List<Jornada>
    suspend fun contarAbiertas(): Long
    suspend fun insertar(jornada: Jornada)
    suspend fun cerrar(id: String, cerradaEn: Long)
}
