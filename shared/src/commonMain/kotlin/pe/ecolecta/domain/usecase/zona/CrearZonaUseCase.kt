package pe.ecolecta.domain.usecase.zona

import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.ZonaRepository

class CrearZonaUseCase(private val zonaRepository: ZonaRepository, private val cuentas: CuentasRepository) {
    suspend operator fun invoke(nombre: String, activo: Boolean = true): Result<Zona> {
        val zona = Zona.crear(nuevoId(), nombre.uppercase(), activo).getOrElse { return Result.failure(it) }
        if (cuentas.existeNombreZona(zona.nombre, "")) return Result.failure(IllegalArgumentException("Ya existe la zona ${zona.nombre}."))
        return runCatching {
            zonaRepository.insertar(zona)
            zona
        }
    }
}
