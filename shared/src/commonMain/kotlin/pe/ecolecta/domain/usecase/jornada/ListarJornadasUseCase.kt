package pe.ecolecta.domain.usecase.jornada

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.repository.JornadaRepository

class ListarJornadasUseCase(private val jornadaRepository: JornadaRepository) {
    suspend operator fun invoke(
        fecha: LocalDate? = null,
        usuarioId: String? = null,
        zonaId: String? = null,
        vehiculoId: String? = null,
    ): List<Jornada> = jornadaRepository.filtrar(fecha, usuarioId, zonaId, vehiculoId)
}
