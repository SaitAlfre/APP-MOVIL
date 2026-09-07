package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.model.ResumenEntregas
import pe.ecolecta.domain.repository.EntregaRepository

class ObtenerResumenEntregasUseCase(private val entregaRepository: EntregaRepository) {
    suspend operator fun invoke(proveedorId: String, desde: Long, hasta: Long): ResumenEntregas {
        val litros = entregaRepository.filtrar(proveedorId = proveedorId)
            .filter { !it.anulada && it.registradoEn in desde until hasta }
            .map { it.litros }

        if (litros.isEmpty()) return ResumenEntregas(0, 0.0, 0.0, 0.0, 0.0)

        return ResumenEntregas(
            numeroEntregas = litros.size,
            litrosTotales = litros.sum(),
            promedioPorEntrega = litros.average(),
            mayorEntrega = litros.max(),
            menorEntrega = litros.min(),
        )
    }
}
