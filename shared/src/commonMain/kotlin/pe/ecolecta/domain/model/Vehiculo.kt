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
            return Result.success(Vehiculo(id, nombre.trim(), placa.trim().uppercase(), activo))
        }
    }
}
