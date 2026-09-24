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
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import pe.ecolecta.domain.usecase.sync.VincularServidorUseCase

class SincronizacionViewModel(
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val observarEntregasUseCase: ObservarEntregasUseCase,
    private val listarProveedoresUseCase: ListarProveedoresUseCase,
    private val sincronizarRegistros: SincronizarRegistrosAcopioUseCase,
    private val vincularServidor: VincularServidorUseCase,
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

            // La cola es de este acopiador: se listan sus propias entregas sin enviar (pendientes y
            // rechazadas, incluidas correcciones y anulaciones aún no confirmadas), no las de toda la
            // zona. Las rechazadas muestran el motivo real que devolvió el servidor.
            val sinEnviar = usuarioId
                ?.let { id ->
                    observarEntregasUseCase(usuarioId = id, syncState = SyncState.PENDING).first() +
                        observarEntregasUseCase(usuarioId = id, syncState = SyncState.ERROR).first()
                }
                .orEmpty()
                .sortedByDescending(Entrega::registradoEn)
            val proveedores = listarProveedoresUseCase().first()

            _uiState.update {
                it.copy(
                    cargando = false,
                    avisoServidor = avisoServidor(usuarioId),
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

    /**
     * Estado REAL del enlace de esta cuenta: se consulta el token guardado, no basta con haber entrado
     * offline. El último motivo del intento de enlace (servidor sin respuesta, PIN distinto) solo vive en
     * memoria; tras reiniciar la app se sigue avisando por el token ausente.
     */
    private suspend fun avisoServidor(usuarioId: String?): String? = when {
        !sincronizarRegistros.servidorConfigurado ->
            "Esta versión no tiene el panel web configurado: el administrador no verá estas entregas hasta instalar una versión con servidor."
        usuarioId == null -> null
        else -> vincularServidor.errores.value[usuarioId]
            ?: if (vincularServidor.enlazado(usuarioId)) null else AVISO_SIN_ENLAZAR
    }

    private companion object {
        const val AVISO_SIN_ENLAZAR =
            "Tu cuenta no está enlazada con el panel web en este celular, así que tus entregas no pueden enviarse todavía. " +
                "Cierra sesión y vuelve a entrar con tu usuario y PIN teniendo conexión: no se pierde ninguna entrega ni tu jornada."
    }

    /** Envía ahora lo pendiente (entregas y "sin recojo") e informa el resultado real, sin maquillarlo. */
    fun reintentar() {
        viewModelScope.launch {
            val mensaje = if (!sincronizarRegistros.configurado) {
                "Este celular no tiene sincronización configurada. Tus datos están guardados solo en este dispositivo."
            } else {
                runCatching { sincronizarRegistros() }.fold(
                    onSuccess = ::mensajeSincronizacion,
                    onFailure = { "No se pudo sincronizar: ${it.message}" },
                )
            }
            _uiState.update { it.copy(mensaje = mensaje) }
            cargar()
        }
    }
}
