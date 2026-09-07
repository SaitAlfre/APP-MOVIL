package pe.ecolecta.domain

sealed class ProveedorSesionException(mensaje: String) : Exception(mensaje) {
    data object SinRolProveedor : ProveedorSesionException("Este usuario no tiene el rol PROVEEDOR.")
    data object SinProveedorAsociado : ProveedorSesionException("Esta cuenta no está vinculada a ningún proveedor.")
}
