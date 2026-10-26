package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Jornada

interface JornadaRepository {
    fun observarTodas(): Flow<List<Jornada>>
    suspend fun obtenerPorId(id: String): Jornada?
    suspend fun obtenerPorUsuarioYFecha(usuarioId: String, fecha: LocalDate): Jornada?
    suspend fun obtenerAbiertaPorZona(zonaId: String): Jornada?
    suspend fun obtenerAbiertaPorUsuario(usuarioId: String): Jornada?
    suspend fun filtrar(fecha: LocalDate?, usuarioId: String?, zonaId: String?, vehiculoId: String?): List<Jornada>
    suspend fun contarAbiertas(): Long
    suspend fun insertar(jornada: Jornada)

    /**
     * Inserta atómicamente si el usuario no tiene otra abierta ni una del mismo día.
     * Devuelve la existente si no insertó. Otros acopiadores pueden compartir la zona.
     */
    suspend fun insertarSiUsuarioLibre(jornada: Jornada): Jornada?

    suspend fun cerrar(id: String, cerradaEn: Long)

    /**
     * Reabre atómicamente si el usuario no tiene otra jornada abierta.
     * Devuelve esa otra jornada si no reabrió, o null si reabrió.
     */
    suspend fun reabrirSiUsuarioLibre(id: String): Jornada?
}
