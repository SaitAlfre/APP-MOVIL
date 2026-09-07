package pe.ecolecta.domain.model

import pe.ecolecta.domain.ZonaInvalidaException

data class Zona(
    val id: String,
    val nombre: String,
    val activo: Boolean,
) {
    companion object {
        fun crear(id: String, nombre: String, activo: Boolean): Result<Zona> {
            if (nombre.isBlank()) return Result.failure(ZonaInvalidaException.NombreVacio)
            return Result.success(Zona(id, nombre.trim(), activo))
        }
    }
}
