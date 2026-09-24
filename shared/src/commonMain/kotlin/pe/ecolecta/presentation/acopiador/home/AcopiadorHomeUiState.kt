package pe.ecolecta.presentation.acopiador.home

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState

data class AcopiadorHomeUiState(
    val cargando: Boolean = true,
    val nombreUsuario: String = "",
    val zonaNombre: String = "",
    val vehiculoInfo: String = "",
    val horaInicio: String = "",
    val litrosHoy: Double = 0.0,
    val entregasHoy: Int = 0,
    val pendientesSync: Int = 0,
    val entregas: List<Entrega> = emptyList(),
    val proveedores: List<Proveedor> = emptyList(),
    val jornadaId: String? = null,
    val jornadaAbierta: Boolean = false,
    val mostrarConfirmacionCierreJornada: Boolean = false,
    val cerrandoJornada: Boolean = false,
    val errorCierreJornada: String? = null,
    /** "HH:mm" del cierre si lo mostrado es la jornada de hoy ya terminada. */
    val horaCierre: String? = null,
    val mostrarDialogoReapertura: Boolean = false,
    val reaperturaRequiereAdmin: Boolean = false,
    val plazoReaperturaMinutos: Int = 0,
    val reabriendo: Boolean = false,
    val errorReapertura: String? = null,
    val mostrarConfirmacionCierreSesion: Boolean = false,
    val cerrandoSesion: Boolean = false,
) {
    /** Hay jornada de hoy pero ya cerrada: se muestra su resumen, no "Abrir jornada". */
    val jornadaTerminada: Boolean get() = jornadaId != null && !jornadaAbierta

    fun nombreProveedor(id: String): String = proveedores.firstOrNull { it.id == id }?.nombres ?: id

    /** Las últimas entregas de la jornada, para la lista corta de la pantalla de inicio. */
    val ultimasEntregas: List<Entrega> get() = entregas.sortedByDescending(Entrega::registradoEn).take(10)

    /** Proveedores de la zona que hoy ya tienen al menos una entrega no anulada registrada. */
    val proveedoresAtendidosHoy: Int
        get() = proveedores.count { proveedor -> entregas.any { it.proveedorId == proveedor.id && !it.anulada } }

    val proveedoresPendientesHoy: Int get() = (proveedores.size - proveedoresAtendidosHoy).coerceAtLeast(0)

    val sincronizadasHoy: Int get() = entregas.count { !it.anulada && it.syncState == SyncState.SYNCED }
    val conflictosHoy: Int get() = entregas.count { !it.anulada && it.syncState == SyncState.CONFLICT }
}
