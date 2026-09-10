package pe.ecolecta.presentation.acopiador.lote

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.ItemLote
import pe.ecolecta.domain.usecase.entrega.RegistrarLoteUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase

class LoteViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val registrarLoteUseCase: RegistrarLoteUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(LoteUiState())
    val uiState: StateFlow<LoteUiState> = _uiState.asStateFlow()

    private var jornada: Jornada? = null
    private var usuarioId: String? = null

    init {
        viewModelScope.launch {
            usuarioId = obtenerSesionUseCase().first()?.usuario?.id
            jornada = obtenerJornadaEnCursoUseCase().first()
            jornada?.let { j ->
                listarProveedoresPorZonaUseCase(j.zonaId).collect { lista -> _uiState.update { it.copy(proveedores = lista) } }
            }
        }
    }

    fun agregarFila() = _uiState.update { it.copy(filas = it.filas + FilaLote()) }

    fun quitarFila(index: Int) = _uiState.update { estado ->
        estado.copy(filas = estado.filas.filterIndexed { i, _ -> i != index }.ifEmpty { listOf(FilaLote()) })
    }

    fun actualizarProveedor(index: Int, proveedorId: String) = actualizarFila(index) { it.copy(proveedorId = proveedorId) }
    fun actualizarLitros(index: Int, valor: String) = actualizarFila(index) { it.copy(litros = valor) }
    fun actualizarTachos(index: Int, valor: String) = actualizarFila(index) { it.copy(tachos = valor) }

    private fun actualizarFila(index: Int, transformar: (FilaLote) -> FilaLote) {
        _uiState.update { estado -> estado.copy(filas = estado.filas.mapIndexed { i, fila -> if (i == index) transformar(fila) else fila }) }
    }

    fun guardar() {
        val estado = _uiState.value
        if (estado.cargando) return
        val j = jornada ?: return
        val uId = usuarioId ?: return
        val items = estado.filas.map { ItemLote(it.proveedorId, it.litros.toDouble(), it.tachos.toInt()) }

        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }
            registrarLoteUseCase(j.id, uId, j.zonaId, j.vehiculoId, items).fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, guardadoExitoso = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }
}
