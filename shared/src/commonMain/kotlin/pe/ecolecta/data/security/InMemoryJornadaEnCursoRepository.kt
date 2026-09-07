package pe.ecolecta.data.security

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaEnCursoRepository

class InMemoryJornadaEnCursoRepository : JornadaEnCursoRepository {
    private val jornada = MutableStateFlow<Jornada?>(null)

    override fun observar(): Flow<Jornada?> = jornada.asStateFlow()

    override suspend fun establecer(jornada: Jornada) {
        this.jornada.value = jornada
    }

    override suspend fun limpiar() {
        jornada.value = null
    }
}
