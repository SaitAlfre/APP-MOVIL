package pe.ecolecta.domain.usecase.proveedor

import pe.ecolecta.domain.extraerProveedorIdDeQr
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
 * servidor (§OFFLINE).
 */
class EscanearQrProveedorUseCase(private val proveedorRepository: ProveedorRepository) {
    suspend operator fun invoke(contenidoQr: String): ResultadoEscaneoQr {
        val proveedorId = extraerProveedorIdDeQr(contenidoQr) ?: return ResultadoEscaneoQr.QrInvalido
        val proveedor = proveedorRepository.obtenerPorId(proveedorId) ?: return ResultadoEscaneoQr.ProveedorNoEncontrado
        return ResultadoEscaneoQr.Encontrado(proveedor)
    }
}
