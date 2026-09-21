package pe.ecolecta.data.security

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.repository.SesionRepository

/**
 * Sesión solo en memoria (se pierde al matar el proceso). Usada únicamente como fake liviano en
 * tests: en producción, todos los roles usan [pe.ecolecta.data.repository.SqlDelightSesionRepository],
 * que persiste la sesión para que el Acopiador (que trabaja offline en campo) no tenga que volver a
 * iniciar sesión cada vez que el sistema operativo mata el proceso.
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
