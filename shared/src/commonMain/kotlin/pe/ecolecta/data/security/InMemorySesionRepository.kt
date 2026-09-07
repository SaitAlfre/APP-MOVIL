package pe.ecolecta.data.security

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.repository.SesionRepository

/**
 * Sesión solo en memoria (se pierde al matar el proceso). Suficiente para ADMIN, que trabaja
 * mayormente conectado; el Acopiador (Fase 3) necesitará sobrevivir el cierre de la app offline
 * y usará una implementación persistente distinta detrás de la misma interfaz.
 */
class InMemorySesionRepository : SesionRepository {
    private val sesion = MutableStateFlow<Sesion?>(null)

    override fun observar(): Flow<Sesion?> = sesion.asStateFlow()

    override suspend fun iniciar(sesion: Sesion) {
        this.sesion.value = sesion
    }

    override suspend fun cerrar() {
        sesion.value = null
    }
}
