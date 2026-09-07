package pe.ecolecta.domain

sealed class ZonaInvalidaException(mensaje: String) : Exception(mensaje) {
    data object NombreVacio : ZonaInvalidaException("El nombre de la zona es obligatorio.")
    data object ConProveedoresActivos : ZonaInvalidaException("No se puede desactivar: la zona tiene proveedores activos.")
}
