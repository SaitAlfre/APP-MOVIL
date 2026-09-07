package pe.ecolecta.domain

sealed class VehiculoInvalidoException(mensaje: String) : Exception(mensaje) {
    data object NombreVacio : VehiculoInvalidoException("El nombre del vehículo es obligatorio.")
    data object PlacaVacia : VehiculoInvalidoException("La placa es obligatoria.")
    data object PlacaDuplicada : VehiculoInvalidoException("Ya existe un vehículo con esa placa.")
}
