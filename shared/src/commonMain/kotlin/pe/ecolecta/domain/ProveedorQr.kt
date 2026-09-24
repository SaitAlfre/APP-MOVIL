package pe.ecolecta.domain

/**
 * El QR de un proveedor codifica únicamente su [Proveedor.codigo] con un prefijo de formato: nunca datos
 * sensibles (DNI, nombre, etc.). Se usa el código porque es lo único que identifica la MISMA ficha en
 * todos lados: el id local es un UUID distinto en cada celular y el panel web usa su propio id entero.
 * Mismo formato que el panel (web: App\Domain\Proveedores\ProveedorQr).
 *
 * Los QR anteriores (`ECOLECTA:PROVEEDOR:<id>`) se siguen leyendo para no invalidar los ya impresos.
 */
private const val PREFIJO_QR_PROVEEDOR = "ECOLECTA:PROVEEDOR:"
private const val PREFIJO_QR_CODIGO = "ECOLECTA:PROVEEDOR:CODIGO:"

fun generarQrProveedor(proveedorCodigo: String): String = "$PREFIJO_QR_CODIGO$proveedorCodigo"

/** Qué identifica un QR de proveedor leído. */
sealed interface ReferenciaQrProveedor {
    data class PorCodigo(val codigo: String) : ReferenciaQrProveedor

    /** Formato anterior: id del celular que lo generó (o del panel web, si era un número). */
    data class PorIdAntiguo(val id: String) : ReferenciaQrProveedor
}

fun leerQrProveedor(contenido: String): ReferenciaQrProveedor? {
    val valor = contenido.trim()
    return when {
        valor.startsWith(PREFIJO_QR_CODIGO) ->
            valor.removePrefix(PREFIJO_QR_CODIGO).ifBlank { null }?.let { ReferenciaQrProveedor.PorCodigo(it) }
        valor.startsWith(PREFIJO_QR_PROVEEDOR) ->
            valor.removePrefix(PREFIJO_QR_PROVEEDOR).ifBlank { null }?.let { ReferenciaQrProveedor.PorIdAntiguo(it) }
        else -> null
    }
}
