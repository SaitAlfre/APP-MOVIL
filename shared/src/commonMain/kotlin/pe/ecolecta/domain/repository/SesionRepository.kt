package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.model.Sesion

interface SesionRepository {
    fun observar(): Flow<Sesion?>
    suspend fun iniciar(sesion: Sesion)
    suspend fun cerrar()
}
