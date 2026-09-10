package pe.ecolecta.presentation.acopiador.qr

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlin.concurrent.Volatile
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.EscanearQrProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ResultadoEscaneoQr
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class EscanearQrViewModel(
    private val escanearQrProveedorUseCase: EscanearQrProveedorUseCase,
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(EscanearQrUiState())
    val uiState: StateFlow<EscanearQrUiState> = _uiState.asStateFlow()

    // @Volatile porque QrScanner puede invocar onCompletion desde el hilo de análisis de la cámara,
    // no necesariamente el hilo principal: sin esto, un escaneo múltiple muy rápido podría no ver
    // a tiempo el cambio hecho por otro hilo y procesar el mismo QR dos veces.
    @Volatile
    private var procesando = false

    /** Ignora lecturas repetidas del mismo cuadro mientras ya se está resolviendo o mostrando un resultado. */
    fun onCodigoEscaneado(contenido: String) {
        if (procesando || _uiState.value.estado !is EstadoEscaneoQr.Escaneando) return
        procesando = true
        viewModelScope.launch {
            when (val resultado = escanearQrProveedorUseCase(contenido)) {
                is ResultadoEscaneoQr.Encontrado -> {
                    val jornada = obtenerJornadaEnCursoUseCase().first()
                    val zonas = listarZonasUseCase().first()
                    val nombreZona = zonas.firstOrNull { it.id == resultado.proveedor.zonaId }?.nombre ?: resultado.proveedor.zonaId
                    _uiState.update {
                        it.copy(
                            estado = EstadoEscaneoQr.Resultado(
                                proveedor = resultado.proveedor,
                                nombreZona = nombreZona,
                                zonaCoincideConJornada = jornada == null || jornada.zonaId == resultado.proveedor.zonaId,
                            ),
                        )
                    }
                }
                ResultadoEscaneoQr.QrInvalido -> _uiState.update {
                    it.copy(estado = EstadoEscaneoQr.Error("Este código QR no corresponde a un proveedor de Ecolecta Huata."))
                }
                ResultadoEscaneoQr.ProveedorNoEncontrado -> _uiState.update {
                    it.copy(estado = EstadoEscaneoQr.Error("No se encontró ningún proveedor registrado con este código."))
                }
            }
            procesando = false
        }
    }

    fun onErrorLectura(mensaje: String) {
        if (_uiState.value.estado !is EstadoEscaneoQr.Escaneando) return
        _uiState.update { it.copy(estado = EstadoEscaneoQr.Error(mensaje.ifBlank { "No se pudo leer el código QR." })) }
    }

    fun reintentar() {
        procesando = false
        _uiState.update { it.copy(estado = EstadoEscaneoQr.Escaneando) }
    }
}
