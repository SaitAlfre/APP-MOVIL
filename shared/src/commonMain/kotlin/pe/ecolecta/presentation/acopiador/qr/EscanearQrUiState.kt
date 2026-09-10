package pe.ecolecta.presentation.acopiador.qr

import pe.ecolecta.domain.model.Proveedor

sealed interface EstadoEscaneoQr {
    data object Escaneando : EstadoEscaneoQr
    data class Resultado(
        val proveedor: Proveedor,
        val nombreZona: String,
        val zonaCoincideConJornada: Boolean,
    ) : EstadoEscaneoQr
    data class Error(val mensaje: String) : EstadoEscaneoQr
}

data class EscanearQrUiState(val estado: EstadoEscaneoQr = EstadoEscaneoQr.Escaneando)
