package pe.ecolecta.presentation.proveedor.entregas

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.DatePeriod
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.minus
import kotlinx.datetime.plus
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.FiltrarMisEntregasUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerHistorialProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerResumenEntregasUseCase

class MisEntregasViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerPerfilProveedorUseCase: ObtenerPerfilProveedorUseCase,
    private val obtenerHistorialProveedorUseCase: ObtenerHistorialProveedorUseCase,
    private val filtrarMisEntregasUseCase: FiltrarMisEntregasUseCase,
    private val obtenerResumenEntregasUseCase: ObtenerResumenEntregasUseCase,
    private val reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(MisEntregasUiState())
    val uiState: StateFlow<MisEntregasUiState> = _uiState.asStateFlow()

    private var proveedorId: String? = null
    private var paginaActual: Int = 0

    init {
        viewModelScope.launch {
            val usuario = obtenerSesionUseCase().first()?.usuario ?: return@launch
            val proveedor = obtenerPerfilProveedorUseCase(usuario.id) ?: return@launch
            proveedorId = proveedor.id
            cargarPrimeraPagina()
            cargarResumen()
        }
    }

    private fun cargarPrimeraPagina() {
        val id = proveedorId ?: return
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true) }
            paginaActual = 0
            val pagina = obtenerHistorialProveedorUseCase(id, pagina = 0)
            _uiState.update {
                it.copy(cargando = false, entregas = pagina, hayMasPaginas = pagina.size >= ObtenerHistorialProveedorUseCase.TAMANO_PAGINA)
            }
        }
    }

    fun cargarMas() {
        val id = proveedorId ?: return
        val estado = _uiState.value
        if (!estado.usaPaginacion || estado.cargandoMas || !estado.hayMasPaginas) return

        viewModelScope.launch {
            _uiState.update { it.copy(cargandoMas = true) }
            paginaActual += 1
            val siguiente = obtenerHistorialProveedorUseCase(id, pagina = paginaActual)
            _uiState.update {
                it.copy(
                    cargandoMas = false,
                    entregas = it.entregas + siguiente,
                    hayMasPaginas = siguiente.size >= ObtenerHistorialProveedorUseCase.TAMANO_PAGINA,
                )
            }
        }
    }

    fun aplicarFiltro(rango: FiltroRangoFecha, estado: SyncState?) {
        val id = proveedorId ?: return
        _uiState.update { it.copy(filtroRango = rango, filtroEstado = estado) }

        if (rango == FiltroRangoFecha.TODOS && estado == null) {
            cargarPrimeraPagina()
            return
        }

        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true) }
            val (desde, hasta) = rangoFiltroAFechas(rango)
            val filtradas = filtrarMisEntregasUseCase(id, syncState = estado, desde = desde, hasta = hasta)
            _uiState.update { it.copy(cargando = false, entregas = filtradas, hayMasPaginas = false) }
        }
    }

    fun cambiarRangoResumen(rango: RangoResumen) {
        _uiState.update { it.copy(rangoResumen = rango) }
        cargarResumen()
    }

    private fun cargarResumen() {
        val id = proveedorId ?: return
        viewModelScope.launch {
            val (desde, hasta) = rangoResumenAFechas(_uiState.value.rangoResumen)
            val resumen = obtenerResumenEntregasUseCase(id, desde, hasta)
            _uiState.update { it.copy(resumen = resumen) }
        }
    }

    private fun rangoFiltroAFechas(rango: FiltroRangoFecha): Pair<Long?, Long?> {
        val zona = TimeZone.currentSystemDefault()
        val hoy = reloj.hoy()
        return when (rango) {
            FiltroRangoFecha.TODOS -> null to null
            FiltroRangoFecha.HOY -> hoy.atStartOfDayIn(zona).toEpochMilliseconds() to
                hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zona).toEpochMilliseconds()
            FiltroRangoFecha.AYER -> {
                val ayer = hoy.minus(DatePeriod(days = 1))
                ayer.atStartOfDayIn(zona).toEpochMilliseconds() to hoy.atStartOfDayIn(zona).toEpochMilliseconds()
            }
            FiltroRangoFecha.ULTIMOS_7 -> hoy.minus(DatePeriod(days = 6)).atStartOfDayIn(zona).toEpochMilliseconds() to
                hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zona).toEpochMilliseconds()
            FiltroRangoFecha.ESTE_MES -> LocalDate(hoy.year, hoy.monthNumber, 1).atStartOfDayIn(zona).toEpochMilliseconds() to
                hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zona).toEpochMilliseconds()
        }
    }

    private fun rangoResumenAFechas(rango: RangoResumen): Pair<Long, Long> {
        val zona = TimeZone.currentSystemDefault()
        val hoy = reloj.hoy()
        val hasta = hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zona).toEpochMilliseconds()
        val desde = when (rango) {
            RangoResumen.HOY -> hoy.atStartOfDayIn(zona).toEpochMilliseconds()
            RangoResumen.SEMANA -> hoy.minus(DatePeriod(days = 6)).atStartOfDayIn(zona).toEpochMilliseconds()
            RangoResumen.MES -> LocalDate(hoy.year, hoy.monthNumber, 1).atStartOfDayIn(zona).toEpochMilliseconds()
        }
        return desde to hasta
    }
}
