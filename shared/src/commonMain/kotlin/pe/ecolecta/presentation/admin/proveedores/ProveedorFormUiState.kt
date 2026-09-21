package pe.ecolecta.presentation.admin.proveedores

import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Zona

data class ProveedorFormUiState(
    val esEdicion: Boolean = false,
    val codigo: String = "",
    val nombres: String = "",
    val dueno: String = "",
    val dni: String = "",
    val telefono: String = "",
    val direccion: String = "",
    val zonaId: String = "",
    val tachos: String = "1",
    val capacidadTachoL: String = "40",
    val estado: EstadoProveedor = EstadoProveedor.ACTIVO,
    val zonas: List<Zona> = emptyList(),
    val cargando: Boolean = false,
    val error: String? = null,
    val guardadoExitoso: Boolean = false,
    val usuariosProveedor: List<Usuario> = emptyList(),
    val usuarioIdVinculado: String? = null,
    val vinculandoUsuario: Boolean = false,
    val errorVinculacion: String? = null,
) {
    val puedeGuardar: Boolean
        get() = codigo.isNotBlank() && nombres.isNotBlank() && dni.isNotBlank() && zonaId.isNotBlank() &&
            (tachos.toIntOrNull() ?: 0) > 0 && (capacidadTachoL.toDoubleOrNull() ?: 0.0) > 0.0
}

sealed interface ProveedorFormUiEvent {
    data class CodigoCambia(val valor: String) : ProveedorFormUiEvent
    data class NombresCambia(val valor: String) : ProveedorFormUiEvent
    data class DuenoCambia(val valor: String) : ProveedorFormUiEvent
    data class DniCambia(val valor: String) : ProveedorFormUiEvent
    data class TelefonoCambia(val valor: String) : ProveedorFormUiEvent
    data class DireccionCambia(val valor: String) : ProveedorFormUiEvent
    data class ZonaCambia(val valor: String) : ProveedorFormUiEvent
    data class TachosCambia(val valor: String) : ProveedorFormUiEvent
    data class CapacidadCambia(val valor: String) : ProveedorFormUiEvent
    data class EstadoCambia(val valor: EstadoProveedor) : ProveedorFormUiEvent
    data class VincularUsuario(val usuarioId: String) : ProveedorFormUiEvent
    data object Guardar : ProveedorFormUiEvent
}
