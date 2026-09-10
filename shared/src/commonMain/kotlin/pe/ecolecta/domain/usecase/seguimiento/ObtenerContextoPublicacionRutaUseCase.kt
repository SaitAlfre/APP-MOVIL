package pe.ecolecta.domain.usecase.seguimiento

import pe.ecolecta.domain.model.ContextoPublicacionRuta
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository

/** Resuelve, una sola vez por sesión de seguimiento, los nombres denormalizados que necesita el
 * documento remoto — evita consultar usuario/zona/vehículo en cada fix GPS. */
class ObtenerContextoPublicacionRutaUseCase(
    private val jornadaRepository: JornadaRepository,
    private val usuarioRepository: UsuarioRepository,
    private val zonaRepository: ZonaRepository,
    private val vehiculoRepository: VehiculoRepository,
) {
    suspend operator fun invoke(usuarioId: String, jornadaId: String): ContextoPublicacionRuta? {
        val jornada = jornadaRepository.obtenerPorId(jornadaId) ?: return null
        val usuario = usuarioRepository.obtenerPorId(usuarioId) ?: return null
        val zona = zonaRepository.obtenerPorId(jornada.zonaId) ?: return null
        val vehiculo = vehiculoRepository.obtenerPorId(jornada.vehiculoId) ?: return null
        return ContextoPublicacionRuta(
            zonaId = zona.id,
            zonaNombre = zona.nombre,
            acopiadorId = usuario.id,
            acopiadorNombre = usuario.nombres,
            vehiculoId = vehiculo.id,
            vehiculoNombre = vehiculo.nombre,
            jornadaId = jornada.id,
            jornadaAbiertaEn = jornada.abiertaEn,
            fecha = jornada.fecha.toString(),
        )
    }
}
