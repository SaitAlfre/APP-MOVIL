package pe.ecolecta.presentation.admin.usuarios

import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario

data class UsuariosUiState(
    val cargando: Boolean = true,
    val usuarios: List<Usuario> = emptyList(),
    val filtroTexto: String = "",
    val filtroRol: Rol? = null,
    val soloActivos: Boolean = false,
) {
    val usuariosFiltrados: List<Usuario>
        get() = usuarios.filter { u ->
            (filtroTexto.isBlank() ||
                u.nombres.contains(filtroTexto, ignoreCase = true) ||
                u.username.contains(filtroTexto, ignoreCase = true) ||
                u.dni.contains(filtroTexto)) &&
                (filtroRol == null || filtroRol in u.roles) &&
                (!soloActivos || u.activo)
        }
}

sealed interface UsuariosUiEvent {
    data class FiltroTextoCambia(val valor: String) : UsuariosUiEvent
    data class FiltroRolCambia(val valor: Rol?) : UsuariosUiEvent
    data class SoloActivosCambia(val valor: Boolean) : UsuariosUiEvent
    data class Desactivar(val id: String) : UsuariosUiEvent
}
