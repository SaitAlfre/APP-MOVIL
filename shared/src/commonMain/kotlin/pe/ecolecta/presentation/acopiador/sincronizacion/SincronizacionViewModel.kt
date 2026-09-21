package pe.ecolecta.presentation.acopiador.sincronizacion

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.ObservarEntregasUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase
import pe.ecolecta.domain.usecase.sync.ResumenColaSync

class SincronizacionViewModel(
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val observarEntregasUseCase: ObservarEntregasUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(SincronizacionUiState())
    val uiState: StateFlow<SincronizacionUiState> = _uiState.asStateFlow()

    init {
        cargar()
    }

    fun cargar() {
        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true) }
            val usuarioId = obtenerSesionUseCase().first()?.usuario?.id
            val resumen = usuarioId?.let { obtenerColaSyncUseCase(it) } ?: ResumenColaSync(0, 0, 0, 0)

            // La cola es de este acopiador: se listan sus propias entregas sin enviar, no las de
            // toda la zona, que es lo que va a reintentar el botón de abajo.
            val sinEnviar = usuarioId
                ?.let { observarEntregasUseCase(usuarioId = it, syncState = SyncState.PENDING).first() }
                .orEmpty()
                .filterNot(Entrega::anulada)
                .sortedByDescending(Entrega::registradoEn)
            val proveedores = listarProveedoresUseCase().first()

            _uiState.update {
                it.copy(
                    cargando = false,
                    resumen = resumen,
                    pendientes = sinEnviar.map { entrega ->
                        EntregaPendiente(
                            entrega = entrega,
                            nombreProveedor = proveedores.firstOrNull { p -> p.id == entrega.proveedorId }?.nombres
                                ?: entrega.proveedorId,
                        )
                    },
                )
            }
        }
    }

    /** No hay backend todavía (Fase 4): esto solo confirma que los datos ya están a salvo localmente. */
    fun reintentar() {
        _uiState.update {
            it.copy(mensaje = "La sincronización con el servidor llega en la Fase 4. Tus datos ya están guardados en este dispositivo.")
        }
    }
}
