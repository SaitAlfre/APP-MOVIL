package pe.ecolecta.presentation.admin.reportes

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Comunicado
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.LiquidacionSemanal
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.model.inicioSemanaProveedor
import pe.ecolecta.domain.repository.ComunicadoRepository
import pe.ecolecta.domain.usecase.admin.AprobarLiquidacionUseCase
import pe.ecolecta.domain.usecase.admin.MarcarLiquidacionPagadaUseCase
import pe.ecolecta.domain.usecase.admin.ObservarLiquidacionesUseCase
import pe.ecolecta.domain.usecase.admin.PublicarComunicadoUseCase
import pe.ecolecta.domain.usecase.admin.fechaLima
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase
import pe.ecolecta.presentation.cargaSegura

data class AdminReportesUiState(
    val cargando: Boolean = true,
    val liquidaciones: List<LiquidacionSemanal> = emptyList(),
    val entregas: List<Entrega> = emptyList(),
    val zonas: List<Zona> = emptyList(),
    val comunicados: List<Comunicado> = emptyList(),
    val semanaActual: LocalDate? = null,
    val procesando: Boolean = false,
    val mensaje: String? = null,
    val error: String? = null,
) {
    /** Litros de la semana en curso por zona, de mayor a menor. */
    val litrosPorZona: List<Pair<String, Double>> = entregas.filter { !it.anulada && semanaActual != null && inicioSemanaProveedor(fechaLima(it.registradoEn)) == semanaActual }
            .groupBy { it.zonaId }
            .map { (zonaId, lista) -> (zonas.firstOrNull { it.id == zonaId }?.nombre ?: zonaId) to lista.sumOf { it.litros } }
            .sortedByDescending { it.second }

    /** Último precio aprobado, para sugerirlo al liquidar la siguiente semana. */
    val ultimoPrecio: Double? = liquidaciones.firstOrNull { it.precio != null }?.precio
}

class AdminReportesViewModel(
    private val observarLiquidaciones: ObservarLiquidacionesUseCase,
    private val aprobarLiquidacion: AprobarLiquidacionUseCase,
    private val marcarPagada: MarcarLiquidacionPagadaUseCase,
    private val publicarComunicado: PublicarComunicadoUseCase,
    private val comunicados: ComunicadoRepository,
    private val observarEntregas: ObservarEntregasUseCase,
    private val listarZonas: ListarZonasUseCase,
    private val obtenerSesion: ObtenerSesionUseCase,
    reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(
        AdminReportesUiState(semanaActual = inicioSemanaProveedor(fechaLima(reloj.ahora().toEpochMilliseconds()))),
    )
    val uiState: StateFlow<AdminReportesUiState> = _uiState.asStateFlow()
    private var sesion: Sesion? = null

    init {
        viewModelScope.launch { obtenerSesion().collect { sesion = it } }
        viewModelScope.launch {
            cargaSegura { observarLiquidaciones().collect { lista -> _uiState.update { it.copy(cargando = false, liquidaciones = lista) } } }
                .onFailure { e -> _uiState.update { it.copy(cargando = false, error = e.message ?: "No se pudieron calcular las liquidaciones.") } }
        }
        viewModelScope.launch { cargaSegura { observarEntregas().collect { lista -> _uiState.update { it.copy(entregas = lista) } } } }
        viewModelScope.launch { cargaSegura { listarZonas().collect { lista -> _uiState.update { it.copy(zonas = lista) } } } }
        viewModelScope.launch { cargaSegura { comunicados.observarTodos().collect { lista -> _uiState.update { it.copy(comunicados = lista) } } } }
    }

    fun aprobar(desde: LocalDate, precioTexto: String) {
        val precio = precioTexto.trim().replace(',', '.').toDoubleOrNull()
        if (precio == null) {
            _uiState.update { it.copy(error = "Ingresa un precio por litro válido, por ejemplo 1.70.") }
            return
        }
        ejecutar { admin -> aprobarLiquidacion(desde, precio, admin.usuario.id).map { "Liquidación aprobada para $it proveedores. Ya la ven en «Mis pagos»." } }
    }

    fun pagar(desde: LocalDate) = ejecutar { admin -> marcarPagada(desde, admin.usuario.id).map { "Liquidación marcada como pagada." } }

    fun publicar(mensaje: String, alPublicar: () -> Unit) = ejecutar { admin ->
        publicarComunicado(mensaje, admin.usuario.id, admin.usuario.nombres).map { alPublicar(); "Comunicado publicado para todos los proveedores." }
    }

    fun eliminarComunicado(id: String) = ejecutar { _ -> runCatching { comunicados.eliminar(id); "Comunicado retirado." } }

    fun limpiarMensaje() = _uiState.update { it.copy(mensaje = null, error = null) }

    private fun ejecutar(accion: suspend (Sesion) -> Result<String>) {
        val actual = sesion ?: run {
            _uiState.update { it.copy(error = "Todavía no se cargó tu sesión. Intenta de nuevo en un momento.") }
            return
        }
        if (_uiState.value.procesando) return
        _uiState.update { it.copy(procesando = true, mensaje = null, error = null) }
        viewModelScope.launch {
            val resultado = cargaSegura { accion(actual).getOrThrow() }
            _uiState.update {
                it.copy(
                    procesando = false,
                    mensaje = resultado.getOrNull(),
                    error = resultado.exceptionOrNull()?.let { e -> e.message ?: "No se pudo completar la acción." },
                )
            }
        }
    }
}
