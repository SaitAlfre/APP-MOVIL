@file:OptIn(ExperimentalCoroutinesApi::class)

package pe.ecolecta.presentation.acopiador.entregas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase

class EntregasDelDiaViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val observarEntregasDeJornadaUseCase: ObservarEntregasDeJornadaUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(EntregasDelDiaUiState())
    val uiState: StateFlow<EntregasDelDiaUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            obtenerJornadaEnCursoUseCase()
                .flatMapLatest { jornada ->
                    if (jornada == null) {
                        flowOf(Triple(emptyList<Entrega>(), emptyList<Proveedor>(), false))
                    } else {
                        combine(
                            observarEntregasDeJornadaUseCase(jornada.id),
                            listarProveedoresPorZonaUseCase(jornada.zonaId),
                        ) { entregas, proveedores -> Triple(entregas, proveedores, jornada.estaAbierta) }
                    }
                }
                .collect { (entregas, proveedores, jornadaAbierta) ->
                    _uiState.update {
                        it.copy(cargando = false, entregas = entregas, proveedores = proveedores, jornadaAbierta = jornadaAbierta)
                    }
                }
        }
    }
}
