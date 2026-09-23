package pe.ecolecta.domain.usecase.zona

import kotlinx.coroutines.flow.first
import pe.ecolecta.domain.ZonaInvalidaException
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ZonaRepository

/**
 * Una zona solo se desactiva cuando ya no la usa nadie: sin proveedores activos, sin jornada
 * abierta y sin acopiadores ni técnicos asignados. Así ningún módulo queda apuntando a una ruta
 * que ya no aparece en los selectores.
 */
class ActualizarZonaUseCase(
    private val zonaRepository: ZonaRepository,
    private val jornadaRepository: JornadaRepository,
    private val cuentas: CuentasRepository,
) {
    suspend operator fun invoke(id: String, nombre: String, activo: Boolean): Result<Unit> {
        val zona = Zona.crear(id, nombre.uppercase(), activo).getOrElse { return Result.failure(it) }
        if (cuentas.existeNombreZona(zona.nombre, id)) return Result.failure(IllegalArgumentException("Ya existe la zona ${zona.nombre}."))
        val anterior = zonaRepository.obtenerPorId(id) ?: return Result.failure(IllegalArgumentException("La zona ya no existe."))
        if (anterior.activo && !activo) {
            if (zonaRepository.contarProveedoresEnZona(id) > 0) return Result.failure(ZonaInvalidaException.ConProveedoresActivos)
            if (jornadaRepository.obtenerAbiertaPorZona(id) != null) {
                return Result.failure(IllegalArgumentException("No se puede desactivar: hay una jornada abierta en esta zona."))
            }
            val asignados = cuentas.observarZonasAsignadas().first().count { it.value == id }
            if (asignados > 0) {
                return Result.failure(IllegalArgumentException("No se puede desactivar: $asignados cuenta(s) tienen esta zona asignada. Reasígnalas en Usuarios y roles."))
            }
        }
        return runCatching { zonaRepository.actualizar(zona) }
    }
}
