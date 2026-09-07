package pe.ecolecta.presentation.proveedor.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import kotlinx.datetime.DatePeriod
import kotlinx.datetime.TimeZone
import kotlinx.datetime.atStartOfDayIn
import kotlinx.datetime.minus
import kotlinx.datetime.plus
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarMisEntregasUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase

class ProveedorHomeViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerPerfilProveedorUseCase: ObtenerPerfilProveedorUseCase,
    private val listarMisEntregasUseCase: ListarMisEntregasUseCase,
    private val reloj: Reloj,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProveedorHomeUiState())
    val uiState: StateFlow<ProveedorHomeUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            val usuario = obtenerSesionUseCase().first()?.usuario ?: return@launch
            val proveedor = obtenerPerfilProveedorUseCase(usuario.id) ?: return@launch

            val zona = TimeZone.currentSystemDefault()
            val hoy = reloj.hoy()
            val inicioHoy = hoy.atStartOfDayIn(zona).toEpochMilliseconds()
            val finHoy = hoy.plus(DatePeriod(days = 1)).atStartOfDayIn(zona).toEpochMilliseconds()
            val inicioSemana = hoy.minus(DatePeriod(days = 6)).atStartOfDayIn(zona).toEpochMilliseconds()

            listarMisEntregasUseCase(proveedor.id).collect { entregas ->
                val vigentes = entregas.filterNot { it.anulada }
                val deHoy = vigentes.filter { it.registradoEn in inicioHoy until finHoy }
                val deLaSemana = vigentes.filter { it.registradoEn >= inicioSemana }

                _uiState.update {
                    it.copy(
                        cargando = false,
                        nombreProveedor = proveedor.nombres,
                        codigoProveedor = proveedor.codigo,
                        estadoProveedor = proveedor.estado,
                        litrosHoy = deHoy.sumOf { e -> e.litros },
                        entregasHoy = deHoy.size,
                        litrosSemana = deLaSemana.sumOf { e -> e.litros },
                        entregasSemana = deLaSemana.size,
                        ultimasEntregas = entregas.sortedByDescending { e -> e.registradoEn }.take(5),
                        sinEntregas = entregas.isEmpty(),
                    )
                }
            }
        }
    }
}
