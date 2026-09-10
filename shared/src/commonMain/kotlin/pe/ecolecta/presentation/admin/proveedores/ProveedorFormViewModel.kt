package pe.ecolecta.presentation.admin.proveedores

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.proveedor.ActualizarProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.CrearProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorUseCase
import pe.ecolecta.domain.usecase.zona.ListarZonasUseCase

class ProveedorFormViewModel(
    private val id: String?,
    private val obtenerProveedorUseCase: ObtenerProveedorUseCase,
    private val crearProveedorUseCase: CrearProveedorUseCase,
    private val actualizarProveedorUseCase: ActualizarProveedorUseCase,
    private val listarZonasUseCase: ListarZonasUseCase,
) : ViewModel() {
    private val _uiState = MutableStateFlow(ProveedorFormUiState(esEdicion = id != null))
    val uiState: StateFlow<ProveedorFormUiState> = _uiState.asStateFlow()

    init {
        viewModelScope.launch {
            listarZonasUseCase(soloActivas = true).collect { zonas ->
                _uiState.update { estado ->
                    estado.copy(zonas = zonas, zonaId = estado.zonaId.ifBlank { zonas.firstOrNull()?.id.orEmpty() })
                }
            }
        }
        if (id != null) {
            viewModelScope.launch {
                val proveedor = obtenerProveedorUseCase(id) ?: return@launch
                _uiState.update {
                    it.copy(
                        codigo = proveedor.codigo,
                        nombres = proveedor.nombres,
                        dni = proveedor.dni,
                        telefono = proveedor.telefono.orEmpty(),
                        direccion = proveedor.direccion.orEmpty(),
                        zonaId = proveedor.zonaId,
                        tachos = proveedor.tachos.toString(),
                        capacidadTachoL = proveedor.capacidadTachoL.toString(),
                        estado = proveedor.estado,
                    )
                }
            }
        }
    }

    fun onEvent(evento: ProveedorFormUiEvent) {
        when (evento) {
            is ProveedorFormUiEvent.CodigoCambia -> _uiState.update { it.copy(codigo = evento.valor, error = null) }
            is ProveedorFormUiEvent.NombresCambia -> _uiState.update { it.copy(nombres = evento.valor, error = null) }
            is ProveedorFormUiEvent.DniCambia -> _uiState.update { it.copy(dni = evento.valor, error = null) }
            is ProveedorFormUiEvent.TelefonoCambia -> _uiState.update { it.copy(telefono = evento.valor) }
            is ProveedorFormUiEvent.DireccionCambia -> _uiState.update { it.copy(direccion = evento.valor) }
            is ProveedorFormUiEvent.ZonaCambia -> _uiState.update { it.copy(zonaId = evento.valor) }
            is ProveedorFormUiEvent.TachosCambia -> _uiState.update { it.copy(tachos = evento.valor) }
            is ProveedorFormUiEvent.CapacidadCambia -> _uiState.update { it.copy(capacidadTachoL = evento.valor) }
            is ProveedorFormUiEvent.EstadoCambia -> _uiState.update { it.copy(estado = evento.valor) }
            ProveedorFormUiEvent.Guardar -> guardar()
        }
    }

    private fun guardar() {
        val estado = _uiState.value
        if (estado.cargando) return
        val tachos = estado.tachos.toIntOrNull() ?: return
        val capacidad = estado.capacidadTachoL.toDoubleOrNull() ?: return

        viewModelScope.launch {
            _uiState.update { it.copy(cargando = true, error = null) }
            val resultado = if (id == null) {
                crearProveedorUseCase(
                    codigo = estado.codigo,
                    nombres = estado.nombres,
                    dni = estado.dni,
                    telefono = estado.telefono.ifBlank { null },
                    direccion = estado.direccion.ifBlank { null },
                    zonaId = estado.zonaId,
                    tachos = tachos,
                    capacidadTachoL = capacidad,
                ).map { }
            } else {
                actualizarProveedorUseCase(
                    id = id,
                    codigoActual = estado.codigo,
                    nombres = estado.nombres,
                    dni = estado.dni,
                    telefono = estado.telefono.ifBlank { null },
                    direccion = estado.direccion.ifBlank { null },
                    zonaId = estado.zonaId,
                    tachos = tachos,
                    capacidadTachoL = capacidad,
                    estado = estado.estado,
                )
            }

            resultado.fold(
                onSuccess = { _uiState.update { it.copy(cargando = false, guardadoExitoso = true) } },
                onFailure = { error -> _uiState.update { it.copy(cargando = false, error = error.message) } },
            )
        }
    }
}
