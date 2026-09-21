@file:OptIn(ExperimentalCoroutinesApi::class)

package pe.ecolecta.presentation.acopiador.lista

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
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.acopiador.ciclo.cicloSimuladoDe
import pe.ecolecta.presentation.acopiador.ciclo.fechaLocalDe

/**
 * Alimenta la pestaña "Lista" del acopiador: quién falta por visitar hoy y cómo va el ciclo.
 *
 * Dos advertencias sobre lo que todavía no es real:
 * - El ciclo sale de [cicloSimuladoDe], un placeholder; ver la nota de ese archivo.
 * - "Sin entrega" vive solo en memoria ([sinEntrega]): no hay tabla ni sincronización detrás, así
 *   que se pierde al cerrar la app. Está para poder validar el flujo de la pantalla, no para
 *   confiar en él como registro.
 */
class ListaProveedoresViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val observarEntregasUseCase: ObservarEntregasUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ListaProveedoresUiState())
    val uiState: StateFlow<ListaProveedoresUiState> = _uiState.asStateFlow()

    /** proveedorId -> motivo (puede ser null si no se indicó). Provisional, sin persistencia. */
    private val sinEntrega = MutableStateFlow<Map<String, String?>>(emptyMap())

    init {
        viewModelScope.launch {
            val zonas = listarZonasUseCase().first()

            obtenerJornadaEnCursoUseCase()
                .flatMapLatest { jornada ->
                    if (jornada == null) {
                        flowOf(Triple(null as Jornada?, emptyList<Proveedor>(), emptyList<Entrega>()))
                    } else {
                        combine(
                            listarProveedoresPorZonaUseCase(jornada.zonaId),
                            observarEntregasUseCase(zonaId = jornada.zonaId),
                        ) { proveedores, entregas -> Triple(jornada, proveedores, entregas) }
                    }
                }
                .combine(sinEntrega) { datos, marcadas -> datos to marcadas }
                .collect { (datos, marcadas) ->
                    val (jornada, proveedores, entregas) = datos
                    if (jornada == null) {
                        _uiState.update { it.copy(cargando = false, jornadaAbierta = false) }
                        return@collect
                    }

                    val nombreZona = zonas.firstOrNull { it.id == jornada.zonaId }?.nombre ?: jornada.zonaId
                    val ciclo = cicloSimuladoDe(jornada.fecha, nombreZona)
                    val vigentes = entregas.filterNot(Entrega::anulada)
                    val deHoy = vigentes.filter { fechaLocalDe(it.registradoEn) == jornada.fecha }

                    val filasHoy = proveedores.map { proveedor ->
                        ProveedorDelDia(
                            proveedor = proveedor,
                            // La última del día: si se corrigió o se registró dos veces, manda la más reciente.
                            entrega = deHoy.filter { it.proveedorId == proveedor.id }.maxByOrNull(Entrega::registradoEn),
                            sinEntrega = marcadas.containsKey(proveedor.id),
                            motivoSinEntrega = marcadas[proveedor.id],
                        )
                    }

                    val porProveedorYDia = vigentes.groupBy { it.proveedorId to fechaLocalDe(it.registradoEn) }
                    val filasCiclo = proveedores.map { proveedor ->
                        FilaCiclo(
                            proveedor = proveedor,
                            celdas = ciclo.dias.map { dia ->
                                val delDia = porProveedorYDia[proveedor.id to dia]
                                CeldaCiclo(
                                    litros = delDia?.sumOf(Entrega::litros),
                                    sinEntrega = delDia == null && dia == jornada.fecha && marcadas.containsKey(proveedor.id),
                                )
                            },
                        )
                    }

                    _uiState.update {
                        it.copy(
                            cargando = false,
                            ciclo = ciclo,
                            proveedores = filasHoy,
                            filasCiclo = filasCiclo,
                            totalCicloL = filasCiclo.sumOf { fila -> fila.celdas.sumOf { c -> c.litros ?: 0.0 } },
                            jornadaAbierta = jornada.estaAbierta,
                        )
                    }
                }
        }
    }

    fun cambiarModo(modo: ModoLista) = _uiState.update { it.copy(modo = modo) }

    fun buscar(texto: String) = _uiState.update { it.copy(busqueda = texto) }

    fun filtrar(filtro: FiltroLista) = _uiState.update { it.copy(filtro = filtro) }

    fun marcarSinEntrega(proveedorId: String, motivo: String?) {
        sinEntrega.update { it + (proveedorId to motivo?.trim()?.ifBlank { null }) }
    }

    fun deshacerSinEntrega(proveedorId: String) {
        sinEntrega.update { it - proveedorId }
    }
}
