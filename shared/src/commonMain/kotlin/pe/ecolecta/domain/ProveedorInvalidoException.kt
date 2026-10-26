package pe.ecolecta.domain

sealed class ProveedorInvalidoException(mensaje: String) : Exception(mensaje) {
    data object CodigoVacio : ProveedorInvalidoException("El código del proveedor es obligatorio.")
    data object NombresVacios : ProveedorInvalidoException("Los nombres son obligatorios.")
    data object DniVacio : ProveedorInvalidoException("El DNI es obligatorio.")
    data object TachosInvalidos : ProveedorInvalidoException("La cantidad de tachos debe estar entre 1 y 1000.")
    data object CapacidadInvalida : ProveedorInvalidoException("La capacidad por tacho debe ser un número finito de al menos 0.01 L.")
    data object CodigoDuplicado : ProveedorInvalidoException("Ya existe un proveedor con ese código.")
    data object DniDuplicado : ProveedorInvalidoException("Ya existe un proveedor con ese DNI.")
}
