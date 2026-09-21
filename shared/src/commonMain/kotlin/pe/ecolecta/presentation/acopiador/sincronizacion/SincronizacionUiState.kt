package pe.ecolecta.presentation.acopiador.sincronizacion

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.usecase.sync.ResumenColaSync

/** Una entrega de la cola, ya resuelta a nombre de proveedor para poder pintarla sin más consultas. */
data class EntregaPendiente(val entrega: Entrega, val nombreProveedor: String)

data class SincronizacionUiState(
    val cargando: Boolean = true,
    val resumen: ResumenColaSync = ResumenColaSync(0, 0, 0, 0),
    val pendientes: List<EntregaPendiente> = emptyList(),
    val mensaje: String? = null,
) {
    val todoSincronizado: Boolean
        get() = resumen.pendientes == 0 && resumen.errores == 0 && resumen.conflictos == 0
}
