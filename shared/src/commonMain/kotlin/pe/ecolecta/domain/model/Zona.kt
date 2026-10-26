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
            if (nombre.trim().length > 100) return Result.failure(IllegalArgumentException("El nombre de la zona admite hasta 100 caracteres."))
            return Result.success(Zona(id, nombre.trim(), activo))
        }
    }
}
