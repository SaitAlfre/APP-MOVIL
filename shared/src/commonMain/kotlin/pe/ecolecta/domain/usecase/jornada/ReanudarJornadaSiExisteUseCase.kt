package pe.ecolecta.domain.usecase.jornada

import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaEnCursoRepository
import pe.ecolecta.domain.repository.JornadaRepository

/**
 * Al entrar al módulo Acopiador, retoma la jornada abierta del usuario si existe — sin importar el
 * día en que se abrió. Antes esto solo miraba la jornada de HOY: una jornada olvidada de un día
 * anterior quedaba huérfana (invisible en la UI, sin forma de cerrarla) pero seguía bloqueando su
 * zona vía [JornadaRepository.obtenerAbiertaPorZona]. Si por algún motivo excepcional hubiera más de
 * una jornada abierta a la vez para el mismo usuario, se retoma la más antigua primero (ver
 * `selectAbiertaPorUsuario` en jornada.sq): el usuario la cierra con el botón normal y, al volver a
 * entrar, esta misma función recupera la siguiente — nunca se elige una arbitrariamente.
 */
class ReanudarJornadaSiExisteUseCase(
    private val jornadaRepository: JornadaRepository,
    private val jornadaEnCursoRepository: JornadaEnCursoRepository,
) {
    suspend operator fun invoke(usuarioId: String): Jornada? {
        val jornada = jornadaRepository.obtenerAbiertaPorUsuario(usuarioId)
        if (jornada != null) jornadaEnCursoRepository.establecer(jornada)
        return jornada
    }
}
