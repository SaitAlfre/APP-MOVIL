package pe.ecolecta.presentation.admin.entregas

import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega

enum class DialogoEntrega { CORREGIR, ANULAR }

data class EntregaDetalleUiState(
    val cargando: Boolean = true,
    val entrega: Entrega? = null,
    val proveedorNombre: String = "",
    /** Por qué ya no se puede corregir ni anular (anulada, en conflicto o semana liquidada); null si se puede. */
    val bloqueo: String? = null,
    /** Correcciones, anulación y resolución de conflictos de esta entrega, de la más reciente a la más antigua. */
    val historial: List<Auditoria> = emptyList(),
    val nombresUsuarios: Map<String, String> = emptyMap(),
    val dialogo: DialogoEntrega? = null,
    val procesando: Boolean = false,
    val errorDialogo: String? = null,
    val mensaje: String? = null,
    val error: String? = null,
) {
    val puedeModificar: Boolean get() = entrega != null && bloqueo == null
    val anulacion: Auditoria? get() = historial.firstOrNull { it.accion == pe.ecolecta.domain.model.AccionAuditoria.ANULAR }
    fun nombreUsuario(id: String): String = nombresUsuarios[id] ?: "Usuario no disponible"
}
