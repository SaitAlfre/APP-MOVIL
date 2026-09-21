package pe.ecolecta.presentation.acopiador.lista

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.acopiador.ciclo.CicloAcopio

enum class FiltroLista(val etiqueta: String) {
    TODOS("Todos"),
    PENDIENTES("Pendientes"),
    REGISTRADOS("Registrados"),
    SIN_ENTREGA("Sin entrega"),
}

/** Las dos vistas de la misma pestaña: el día de hoy en detalle, o el ciclo completo en tabla. */
enum class ModoLista { HOY, SEMANA }

enum class EstadoProveedorDia {
    /** Todavía no pasó el acopiador: la fila invita a registrar. */
    POR_REGISTRAR,

    /** Entrega registrada y ya confirmada por el servidor. */
    REGISTRADO,

    /** Registrada en el dispositivo, aún en la cola de sincronización. */
    POR_SINCRONIZAR,

    /** El acopiador marcó que hoy este proveedor no entrega. */
    SIN_ENTREGA,
}

data class ProveedorDelDia(
    val proveedor: Proveedor,
    val entrega: Entrega?,
    val sinEntrega: Boolean,
    val motivoSinEntrega: String? = null,
) {
    val estado: EstadoProveedorDia
        get() = when {
            sinEntrega -> EstadoProveedorDia.SIN_ENTREGA
            entrega == null -> EstadoProveedorDia.POR_REGISTRAR
            entrega.syncState == SyncState.SYNCED -> EstadoProveedorDia.REGISTRADO
            else -> EstadoProveedorDia.POR_SINCRONIZAR
        }
}

/** Una celda de la tabla del ciclo: litros del día, o la marca de que ese día no hubo entrega. */
data class CeldaCiclo(val litros: Double?, val sinEntrega: Boolean = false)

data class FilaCiclo(val proveedor: Proveedor, val celdas: List<CeldaCiclo>)

data class ListaProveedoresUiState(
    val cargando: Boolean = true,
    val modo: ModoLista = ModoLista.HOY,
    val ciclo: CicloAcopio? = null,
    val proveedores: List<ProveedorDelDia> = emptyList(),
    val filasCiclo: List<FilaCiclo> = emptyList(),
    val totalCicloL: Double = 0.0,
    val busqueda: String = "",
    val filtro: FiltroLista = FiltroLista.TODOS,
    val jornadaAbierta: Boolean = false,
) {
    val registrados: Int get() = proveedores.count { it.estado == EstadoProveedorDia.REGISTRADO || it.estado == EstadoProveedorDia.POR_SINCRONIZAR }
    val pendientes: Int get() = proveedores.count { it.estado == EstadoProveedorDia.POR_REGISTRAR }

    /** "25 proveedores · 8 registrados · 14 pendientes" */
    val resumen: String get() = "${proveedores.size} proveedores · $registrados registrados · $pendientes pendientes"

    val visibles: List<ProveedorDelDia>
        get() {
            val texto = busqueda.trim().lowercase()
            return proveedores
                .filter { fila ->
                    texto.isBlank() ||
                        fila.proveedor.nombres.lowercase().contains(texto) ||
                        fila.proveedor.codigo.lowercase().contains(texto)
                }
                .filter { fila ->
                    when (filtro) {
                        FiltroLista.TODOS -> true
                        FiltroLista.PENDIENTES -> fila.estado == EstadoProveedorDia.POR_REGISTRAR
                        FiltroLista.REGISTRADOS -> fila.estado == EstadoProveedorDia.REGISTRADO ||
                            fila.estado == EstadoProveedorDia.POR_SINCRONIZAR
                        FiltroLista.SIN_ENTREGA -> fila.estado == EstadoProveedorDia.SIN_ENTREGA
                    }
                }
        }
}
