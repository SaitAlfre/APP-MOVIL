package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.ProveedorInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

class ActualizarProveedorUseCase(
    private val proveedorRepository: ProveedorRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(
        id: String,
        codigoActual: String,
        nombres: String,
        dni: String,
        telefono: String?,
        direccion: String?,
        zonaId: String,
        tachos: Int,
        capacidadTachoL: Double,
        estado: EstadoProveedor,
    ): Result<Unit> {
        if (proveedorRepository.existeDni(dni.trim(), id)) {
            return Result.failure(ProveedorInvalidoException.DniDuplicado)
        }

        val proveedor = Proveedor.crear(
            id = id,
            codigo = codigoActual,
            nombres = nombres,
            dni = dni,
            telefono = telefono,
            direccion = direccion,
            zonaId = zonaId,
            tachos = tachos,
            capacidadTachoL = capacidadTachoL,
            estado = estado,
            updatedAt = reloj.ahora().toEpochMilliseconds(),
        ).getOrElse { return Result.failure(it) }

        return runCatching { proveedorRepository.actualizar(proveedor) }
    }
}
