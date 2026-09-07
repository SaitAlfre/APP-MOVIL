package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.repository.ProveedorRepository

/** Eliminación lógica: nunca se borra físicamente para conservar la trazabilidad histórica (§8). */
class RetirarProveedorUseCase(
    private val proveedorRepository: ProveedorRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(id: String): Result<Unit> = runCatching {
        proveedorRepository.cambiarEstado(id, EstadoProveedor.RETIRADO, reloj.ahora().toEpochMilliseconds())
    }
}
