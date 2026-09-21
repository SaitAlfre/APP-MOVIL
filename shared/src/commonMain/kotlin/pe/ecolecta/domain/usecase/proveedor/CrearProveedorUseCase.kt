package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.ProveedorInvalidoException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.ProveedorRepository

class CrearProveedorUseCase(
    private val proveedorRepository: ProveedorRepository,
    private val reloj: Reloj,
) {
    suspend operator fun invoke(
        codigo: String,
        nombres: String,
        dni: String,
        telefono: String?,
        direccion: String?,
        zonaId: String,
        tachos: Int = 1,
        capacidadTachoL: Double = 40.0,
        dueno: String? = null,
    ): Result<Proveedor> {
        if (proveedorRepository.existeCodigo(codigo.trim(), "")) {
            return Result.failure(ProveedorInvalidoException.CodigoDuplicado)
        }
        if (proveedorRepository.existeDni(dni.trim(), "")) {
            return Result.failure(ProveedorInvalidoException.DniDuplicado)
        }

        val proveedor = Proveedor.crear(
            id = nuevoId(),
            codigo = codigo,
            nombres = nombres,
            dni = dni,
            telefono = telefono,
            direccion = direccion,
            zonaId = zonaId,
            tachos = tachos,
            capacidadTachoL = capacidadTachoL,
            updatedAt = reloj.ahora().toEpochMilliseconds(),
        ).getOrElse { return Result.failure(it) }.copy(dueno = dueno?.trim()?.ifBlank { null })

        return runCatching {
            proveedorRepository.insertar(proveedor)
            proveedor
        }
    }
}
