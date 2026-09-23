package pe.ecolecta.presentation.acopiador.nav

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.sync.ObtenerColaSyncUseCase

/**
 * Cuenta de pendientes por sincronizar para el globo de la pestaña "Sincronizar" del tab bar, que
 * es visible desde CUALQUIER pestaña (Home/Proveedores/Entregas/Perfil), no solo dentro de la
 * pantalla de sincronización. Vive en el Shell —no en cada ViewModel de pestaña— para no duplicar
 * la consulta a la cola cinco veces.
 */
class AcopiadorBadgeViewModel(
    private val obtenerSesionUseCase: ObtenerSesionUseCase,
    private val obtenerColaSyncUseCase: ObtenerColaSyncUseCase,
) : ViewModel() {
    private val _pendientes = MutableStateFlow(0)
    val pendientes: StateFlow<Int> = _pendientes.asStateFlow()

    init {
        viewModelScope.launch {
            val usuarioId = obtenerSesionUseCase().first()?.usuario?.id ?: return@launch
            // Sin un flujo reactivo de la cola de salida, se vuelve a consultar cada pocos segundos
            // mientras el Shell está en pantalla: suficiente para reflejar un registro nuevo sin
            // sondear en un bucle ajustado.
            while (true) {
                _pendientes.value = obtenerColaSyncUseCase(usuarioId).pendientes
                delay(4000)
            }
        }
    }
}
