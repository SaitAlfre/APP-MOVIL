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
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.acopio.MotivoSinRecojo
import pe.ecolecta.domain.acopio.cicloAcopioDe
import pe.ecolecta.domain.acopio.construirFilasAcopio
import pe.ecolecta.domain.acopio.hoyAcopio
import pe.ecolecta.domain.repository.SinRecojoRepository
import pe.ecolecta.domain.usecase.acopio.DeshacerSinRecojoUseCase
import pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

/**
 * Lista de acopio del acopiador (reemplaza el seguimiento GPS): los proveedores activos de la zona
 * de su jornada, con el estado de hoy y la hoja de los 6 días del ciclo. Todo sale de la base local
 * (entregas y "sin recojo" persistidos), así que sobrevive a cerrar y volver a abrir la app.
 */
class ListaProveedoresViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val observarEntregasUseCase: ObservarEntregasUseCase,
    private val sinRecojoRepository: SinRecojoRepository,
    private val listarZonasUseCase: ListarZonasUseCase,
    private val marcarSinRecojoUseCase: MarcarSinRecojoUseCase,
    private val deshacerSinRecojoUseCase: DeshacerSinRecojoUseCase,
    private val sincronizar: SincronizarRegistrosAcopioUseCase,
    private val reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ListaProveedoresUiState(remotoConfigurado = sincronizar.configurado))
    val uiState: StateFlow<ListaProveedoresUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            val zonas = listarZonasUseCase().first()

            obtenerJornadaEnCursoUseCase()
                .flatMapLatest { jornada ->
                    if (jornada == null) {
                        flowOf(null)
                    } else {
                        val zonaNombre = zonas.firstOrNull { it.id == jornada.zonaId }?.nombre ?: jornada.zonaId
                        val hoy = hoyAcopio(reloj)
                        val ciclo = cicloAcopioDe(hoy, zonaNombre)
                        combine(
                            listarProveedoresPorZonaUseCase(jornada.zonaId),
                            observarEntregasUseCase(zonaId = jornada.zonaId),
                            sinRecojoRepository.observarPorZona(jornada.zonaId, ciclo.inicio, ciclo.fin),
                        ) { proveedores, entregas, marcas ->
                            _uiState.value.copy(
                                cargando = false,
                                ciclo = ciclo,
                                hoy = hoy,
                                zonaNombre = zonaNombre,
                                jornadaId = jornada.id,
                                jornadaAbierta = jornada.estaAbierta,
                                filas = construirFilasAcopio(
                                    ciclo = ciclo,
                                    zonaId = jornada.zonaId,
                                    proveedores = proveedores,
                                    entregas = entregas,
                                    marcas = marcas,
                                    remotoDisponible = sincronizar.configurado,
                                ),
                            )
                        }
                    }
                }
                .collect { nuevo ->
                    _uiState.value = nuevo ?: _uiState.value.copy(cargando = false, ciclo = null, filas = emptyList(), jornadaAbierta = false)
                }
        }
    }

    fun cambiarModo(modo: ModoLista) = _uiState.update { it.copy(modo = modo) }

    fun buscar(texto: String) = _uiState.update { it.copy(busqueda = texto) }

    fun filtrar(filtro: FiltroLista) = _uiState.update { it.copy(filtro = filtro) }

    fun abrirDetalle(proveedorId: String) = _uiState.update { it.copy(detalleProveedorId = proveedorId) }

    fun cerrarDetalle() = _uiState.update { it.copy(detalleProveedorId = null) }

    fun descartarError() = _uiState.update { it.copy(error = null) }

    fun marcarSinRecojo(proveedorId: String, motivo: MotivoSinRecojo, detalle: String?) = ejecutar { usuarioId, jornadaId ->
        marcarSinRecojoUseCase(jornadaId, proveedorId, usuarioId, motivo, detalle).map { }
    }

    fun deshacerSinRecojo(marcaId: String) = ejecutar { usuarioId, _ -> deshacerSinRecojoUseCase(marcaId, usuarioId) }

    fun sincronizarAhora() {
        viewModelScope.launch { runCatching { sincronizar() } }
    }

    private fun ejecutar(accion: suspend (usuarioId: String, jornadaId: String) -> Result<Unit>) {
        if (_uiState.value.procesando) return
        val jornadaId = _uiState.value.jornadaId ?: return
        _uiState.update { it.copy(procesando = true, error = null) }
        viewModelScope.launch {
            val usuarioId = obtenerSesionUseCase().first()?.usuario?.id
            val resultado = if (usuarioId == null) {
                Result.failure(IllegalStateException("La sesión terminó. Vuelve a ingresar."))
            } else {
                accion(usuarioId, jornadaId)
            }
            _uiState.update { it.copy(procesando = false, error = resultado.exceptionOrNull()?.message) }
            // Se intenta enviar enseguida; si no hay conexión queda pendiente y se reintenta solo.
            if (resultado.isSuccess) runCatching { sincronizar() }
        }
    }
}
