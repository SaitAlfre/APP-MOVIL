package pe.ecolecta.presentation.acopiador.registro

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ListarEntregasUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import pe.ecolecta.domain.usecase.jornada.ObtenerJornadaEnCursoUseCase
import pe.ecolecta.domain.usecase.proveedor.ListarProveedoresPorZonaUseCase

class RegistroEntregaViewModel(
    private val obtenerJornadaEnCursoUseCase: ObtenerJornadaEnCursoUseCase,
    private val listarProveedoresPorZonaUseCase: ListarProveedoresPorZonaUseCase,
    private val listarEntregasUseCase: ListarEntregasUseCase,
    private val registrarEntregaUseCase: RegistrarEntregaUseCase,
    private val corregirEntregaUseCase: CorregirEntregaUseCase,
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    proveedorIdPreseleccionado: String? = null,
) : ViewModel() {
    private val _uiState = MutableStateFlow(RegistroEntregaUiState(proveedorId = proveedorIdPreseleccionado.orEmpty()))
    val uiState: StateFlow<RegistroEntregaUiState> = _uiState.asStateFlow()

    private var jornada: Jornada? = null
    private var usuarioId: String? = null

    init {
        viewModelScope.launch {
            usuarioId = obtenerSesionUseCase().first()?.usuario?.id
            jornada = obtenerJornadaEnCursoUseCase().first()
            jornada?.let { j ->
                listarProveedoresPorZonaUseCase(j.zonaId).collect { lista ->
                    _uiState.update { it.copy(proveedores = lista, proveedorId = it.proveedorId.ifBlank { lista.firstOrNull()?.id.orEmpty() }) }
                }
            }
        }
    }

    fun onProveedorCambia(id: String) = _uiState.update { it.copy(proveedorId = id, error = null) }
    fun onLitrosCambia(valor: String) = _uiState.update { it.copy(litros = valor, error = null) }
    fun aplicarPreset(valor: Double) = _uiState.update { it.copy(litros = if (valor == valor.toLong().toDouble()) valor.toLong().toString() else valor.toString()) }
    fun onTachosCambia(valor: String) = _uiState.update { it.copy(tachos = valor) }
    fun onObservacionesCambia(valor: String) = _uiState.update { it.copy(observaciones = valor) }

    fun guardar(omitirChequeoDuplicado: Boolean = false) {
        val estado = _uiState.value
        if (estado.cargando) return
        val j = jornada ?: return
        val uId = usuarioId ?: return
        val litros = estado.litros.toDoubleOrNull() ?: return
        val tachos = estado.tachos.toIntOrNull() ?: return

        // cargando se marca aquí, ANTES de la corrutina y de cualquier consulta async: si se marcara
        // recién después del chequeo de duplicado (que es una consulta suspend), un doble-tap podría
        // disparar dos veces guardar() antes de que la primera consulta resuelva, y ambas pasarían el
        // chequeo de duplicado (ninguna ve la entrega de la otra todavía) => dos entregas duplicadas.
        _uiState.update { it.copy(cargando = true, error = null) }

        viewModelScope.launch {
            if (!omitirChequeoDuplicado) {
                val existentes = listarEntregasUseCase(jornadaId = j.id, proveedorId = estado.proveedorId).filterNot { it.anulada }
                if (existentes.isNotEmpty()) {
                    _uiState.update { it.copy(cargando = false, entregaDuplicada = EntregaExistente(existentes.first(), litros, tachos)) }
                    return@launch
                }
            }

            registrarEntregaUseCase(
                jornadaId = j.id,
                proveedorId = estado.proveedorId,
                usuarioId = uId,
                zonaId = j.zonaId,
                vehiculoId = j.vehiculoId,
                litros = litros,
                tachos = tachos,
                observaciones = estado.observaciones.ifBlank { null },
            ).fold(
                onSuccess = { resultado ->
                    _uiState.update { it.copy(cargando = false, guardadoExitoso = true, advertenciaDesviacion = resultado.advertenciaDesviacion) }
                },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }

    fun sumarADuplicada() {
        val estado = _uiState.value
        if (estado.cargando) return
        val duplicada = estado.entregaDuplicada ?: return
        val uId = usuarioId ?: return

        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, entregaDuplicada = null) }
            corregirEntregaUseCase(
                entregaId = duplicada.entrega.id,
                litros = duplicada.entrega.litros + duplicada.litrosNuevos,
                tachos = duplicada.entrega.tachos + duplicada.tachosNuevos,
                observaciones = duplicada.entrega.observaciones,
                motivo = "Sumado a la entrega existente del mismo proveedor en esta jornada.",
                usuarioId = uId,
            ).fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, guardadoExitoso = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }

    fun registrarAparte() {
        _uiState.update { it.copy(entregaDuplicada = null) }
        guardar(omitirChequeoDuplicado = true)
    }

    fun cerrarAvisoDuplicado() = _uiState.update { it.copy(entregaDuplicada = null) }
}
