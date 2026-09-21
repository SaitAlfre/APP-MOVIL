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
     * Inserta [jornada] solo si la zona no tiene ya una jornada abierta de un usuario distinto, todo
     * dentro de una única operación atómica — evita la condición de carrera entre comprobar la zona
     * e insertar que permitiría, con dos llamadas solapadas (p. ej. doble tap), abrir dos jornadas en
     * la misma zona. Devuelve la jornada que ya ocupa la zona si no se insertó, o null si se insertó.
     */
    suspend fun insertarSiZonaLibre(jornada: Jornada): Jornada?

    suspend fun cerrar(id: String, cerradaEn: Long)
}
