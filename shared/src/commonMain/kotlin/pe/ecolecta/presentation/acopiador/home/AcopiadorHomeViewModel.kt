@file:OptIn(ExperimentalCoroutinesApi::class)

package pe.ecolecta.presentation.acopiador.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flatMapLatest
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasDeJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase

class AcopiadorHomeViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val observarEntregasDeJornadaUseCase: ObservarEntregasDeJornadaUseCase,
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val corregirEntregaUseCase: CorregirEntregaUseCase,
    private val anularEntregaUseCase: AnularEntregaUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(AcopiadorHomeUiState())
    val uiState: StateFlow<AcopiadorHomeUiState> = _uiState.asStateFlow()

    private var usuarioIdActual: String? = null

    init {
        viewModelScope.launch {
            val usuarioId = obtenerSesionUseCase().first()?.usuario?.id
            usuarioIdActual = usuarioId

            obtenerJornadaEnCursoUseCase()
                .flatMapLatest { jornada ->
                    if (jornada == null) {
                        flowOf(Triple(emptyList<Entrega>(), emptyList<Proveedor>(), 0))
                    } else {
                        combine(
                            observarEntregasDeJornadaUseCase(jornada.id),
                            listarProveedoresPorZonaUseCase(jornada.zonaId),
                        ) { entregas, proveedores -> Triple(entregas, proveedores, 0) }
                    }
                }
                .collect { (entregas, proveedores, _) ->
                    val pendientes = usuarioId?.let { obtenerColaSyncUseCase(it).pendientes } ?: 0
                    _uiState.update {
                        it.copy(
                            cargando = false,
                            litrosHoy = entregas.filterNot(Entrega::anulada).sumOf(Entrega::litros),
                            entregasHoy = entregas.count { e -> !e.anulada },
                            pendientesSync = pendientes,
                            ultimasEntregas = entregas.sortedByDescending(Entrega::registradoEn).take(10),
                            proveedores = proveedores,
                        )
                    }
                }
        }
    }

    fun corregir(entregaId: String, litros: Double, tachos: Int, motivo: String) {
        val usuarioId = usuarioIdActual ?: return
        viewModelScope.launch { corregirEntregaUseCase(entregaId, litros, tachos, observaciones = null, motivo = motivo, usuarioId = usuarioId) }
    }

    fun anular(entregaId: String, motivo: String) {
        val usuarioId = usuarioIdActual ?: return
        viewModelScope.launch { anularEntregaUseCase(entregaId, motivo, usuarioId) }
    }
}
