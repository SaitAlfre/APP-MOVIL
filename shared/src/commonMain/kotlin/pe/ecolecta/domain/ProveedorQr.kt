package pe.ecolecta.domain

/**
 * El QR de un proveedor codifica únicamente su [Proveedor.id] (UUID no secuencial, ya opaco) con
 * un prefijo de formato: nunca datos sensibles (DNI, nombre, etc.). Esto permite al ACOPIADOR
 * ubicarlo de inmediato en el repositorio local, y a la vez distinguir "QR ajeno a Ecolecta" de
 * "proveedor eliminado/inexistente" al momento de escanear.
 */
private const val PREFIJO_QR_PROVEEDOR = "ECOLECTA:PROVEEDOR:"

fun generarQrProveedor(proveedorId: String): String = "$PREFIJO_QR_PROVEEDOR$proveedorId"

fun extraerProveedorIdDeQr(contenido: String): String? {
    val valor = contenido.trim()
    if (!valor.startsWith(PREFIJO_QR_PROVEEDOR)) return null
    return valor.removePrefix(PREFIJO_QR_PROVEEDOR).ifBlank { null }
}
