package pe.ecolecta.presentation.admin.entregas

import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState

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
    /** Cuenta cuyo token firma el envío al panel: quien la registró o, si la corrigió/anuló, quien lo hizo. */
    val remitenteId: String? = null,
    /** Si esa cuenta tiene sesión vigente con el panel en este celular; null si no hay panel configurado. */
    val remitenteEnlazado: Boolean? = null,
    val reintentando: Boolean = false,
) {
    val puedeModificar: Boolean get() = entrega != null && bloqueo == null
    val anulacion: Auditoria? get() = historial.firstOrNull { it.accion == pe.ecolecta.domain.model.AccionAuditoria.ANULAR }
    fun nombreUsuario(id: String): String = nombresUsuarios[id] ?: "Usuario no disponible"

    /** Solo si aún falta enviarla al panel web (pendiente o rechazada). */
    val faltaEnviar: Boolean
        get() = entrega?.syncState == SyncState.PENDING || entrega?.syncState == SyncState.ERROR

    /** El reintento solo sirve si hay panel y la cuenta que firma está enlazada en este celular. */
    val puedeReintentar: Boolean get() = faltaEnviar && remitenteEnlazado == true

    /**
     * Qué pasa con el envío y quién debe actuar, según lo que la app realmente hace: la cola se envía sola
     * cada pocos minutos, firmada por la cuenta del autor del último cambio, nunca por ADMIN en nombre del
     * acopiador. Null si ya no falta enviarla.
     */
    val explicacionEnvio: String?
        get() {
            val e = entrega ?: return null
            if (!faltaEnviar) return null
            val remitente = remitenteId ?: e.usuarioId
            val quien = nombreUsuario(remitente)
            val papel = if (remitente == e.usuarioId) "quien la registró" else "autor de la última corrección o anulación"
            val envio = when (remitenteEnlazado) {
                null -> "Esta versión no tiene el panel web configurado: la entrega solo está guardada en este teléfono."
                true -> "Se envía con la cuenta de $quien ($papel), enlazada en este teléfono. El teléfono la reintenta solo " +
                    "cada pocos minutos; también puedes reintentar ahora."
                false -> "Solo puede enviarla la cuenta de $quien ($papel), y no está enlazada con el panel web en este teléfono. " +
                    "$quien debe iniciar sesión aquí con conexión; se enviará sola al enlazarse. Reintentar antes no tendría efecto " +
                    "y ADMIN no la envía en su nombre."
            }
            val rechazo = if (e.syncState == SyncState.ERROR) {
                " El servidor la rechazó: corrige en el panel web el dato indicado en el motivo (debe existir con el mismo " +
                    "código, nombre o placa) y luego reintenta."
            } else {
                ""
            }
            return envio + rechazo
        }
}
