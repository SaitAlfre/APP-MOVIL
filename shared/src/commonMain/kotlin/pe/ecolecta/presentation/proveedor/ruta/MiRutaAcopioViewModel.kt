package pe.ecolecta.presentation.proveedor.ruta

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.usecase.proveedor.ObtenerRutaAcopioUseCase

class MiRutaAcopioViewModel(
    private val obtenerRutaAcopioUseCase: ObtenerRutaAcopioUseCase,
    private val reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(MiRutaAcopioUiState())
    val uiState: StateFlow<MiRutaAcopioUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            obtenerRutaAcopioUseCase().collect { estado ->
                _uiState.update { it.copy(cargando = false, estado = estado, ahoraMs = reloj.ahora().toEpochMilliseconds()) }
            }
        }
        // Refresca "hace X min" periódicamente aunque no llegue ningún dato nuevo.
        viewModelScope.launch {
            while (true) {
                delay(30_000)
                _uiState.update { it.copy(ahoraMs = reloj.ahora().toEpochMilliseconds()) }
            }
        }
    }
}
