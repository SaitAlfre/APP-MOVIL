package pe.ecolecta.domain.usecase.traslado

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.TrasladoRepository

class CrearTrasladoUseCase(
    private val trasladoRepository: TrasladoRepository,
    private val proveedorRepository: ProveedorRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(proveedorId: String, zonaDestinoId: String, motivo: String?): Result<TrasladoZona> {
        val proveedor = proveedorRepository.obtenerPorId(proveedorId)
            ?: return Result.failure(IllegalStateException("Proveedor no encontrado"))

        val traslado = TrasladoZona.crear(
            id = nuevoId(),
            proveedorId = proveedorId,
            zonaOrigenId = proveedor.zonaId,
            zonaDestinoId = zonaDestinoId,
            motivo = motivo,
            creadoEn = reloj.ahora().toEpochMilliseconds(),
        ).getOrElse { return Result.failure(it) }

        return runCatching {
            trasladoRepository.insertar(traslado)
            traslado
        }
    }
}
