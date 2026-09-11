package pe.ecolecta.domain

sealed class ProveedorInvalidoException(mensaje: String) : Exception(mensaje) {
    data object CodigoVacio : ProveedorInvalidoException("El código del proveedor es obligatorio.")
    data object NombresVacios : ProveedorInvalidoException("Los nombres son obligatorios.")
    data object DniVacio : ProveedorInvalidoException("El DNI es obligatorio.")
    data object TachosInvalidos : ProveedorInvalidoException("La cantidad de tachos debe ser mayor a 0.")
    data object CapacidadInvalida : ProveedorInvalidoException("La capacidad por tacho debe ser mayor a 0.")
    data object CodigoDuplicado : ProveedorInvalidoException("Ya existe un proveedor con ese código.")
    data object DniDuplicado : ProveedorInvalidoException("Ya existe un proveedor con ese DNI.")
    data object UsuarioSinRolProveedor : ProveedorInvalidoException("El usuario seleccionado no tiene el rol PROVEEDOR.")
    data object UsuarioYaVinculado : ProveedorInvalidoException("Ese usuario ya está vinculado a otro proveedor.")
}
