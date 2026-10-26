package pe.ecolecta.domain.model

import pe.ecolecta.domain.VehiculoInvalidoException

data class Vehiculo(
    val id: String,
    val nombre: String,
    val placa: String,
    val activo: Boolean,
) {
    companion object {
        fun crear(id: String, nombre: String, placa: String, activo: Boolean): Result<Vehiculo> {
            if (nombre.isBlank()) return Result.failure(VehiculoInvalidoException.NombreVacio)
            if (placa.isBlank()) return Result.failure(VehiculoInvalidoException.PlacaVacia)
            if (nombre.trim().length > 100) return Result.failure(IllegalArgumentException("El nombre admite hasta 100 caracteres."))
            if (placa.trim().length > 20) return Result.failure(IllegalArgumentException("La placa admite hasta 20 caracteres."))
            return Result.success(Vehiculo(id, nombre.trim(), placa.trim().uppercase(), activo))
        }
    }
}
