package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.ReferenciaQrProveedor
import pe.ecolecta.domain.leerQrProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.repository.ProveedorRepository

sealed interface ResultadoEscaneoQr {
    data class Encontrado(val proveedor: Proveedor) : ResultadoEscaneoQr
    data object QrInvalido : ResultadoEscaneoQr
    data object ProveedorNoEncontrado : ResultadoEscaneoQr
}

/**
 * Resuelve el proveedor a partir del contenido de un QR escaneado. Consulta únicamente el
 * repositorio local (offline-first): el registro de entrega nunca depende de una petición al
 * servidor (§OFFLINE). El QR actual lleva el código del proveedor, que coincide en todos los
 * celulares y en el panel; un QR antiguo lleva un id que solo existe en el celular que lo generó.
 */
class EscanearQrProveedorUseCase(private val proveedorRepository: ProveedorRepository) {
    suspend operator fun invoke(contenidoQr: String): ResultadoEscaneoQr {
        val proveedor = when (val referencia = leerQrProveedor(contenidoQr) ?: return ResultadoEscaneoQr.QrInvalido) {
            is ReferenciaQrProveedor.PorCodigo -> proveedorRepository.obtenerPorCodigo(referencia.codigo)
            is ReferenciaQrProveedor.PorIdAntiguo -> proveedorRepository.obtenerPorId(referencia.id)
        }
        return proveedor?.let { ResultadoEscaneoQr.Encontrado(it) } ?: ResultadoEscaneoQr.ProveedorNoEncontrado
    }
}
