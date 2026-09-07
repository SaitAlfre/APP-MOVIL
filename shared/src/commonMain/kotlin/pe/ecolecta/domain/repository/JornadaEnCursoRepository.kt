package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Jornada

/** Puntero en memoria a la jornada activa del acopiador en esta sesión de app (distinto de la persistencia en SQLDelight). */
interface JornadaEnCursoRepository {
    fun observar(): Flow<Jornada?>
    suspend fun establecer(jornada: Jornada)
    suspend fun limpiar()
}
