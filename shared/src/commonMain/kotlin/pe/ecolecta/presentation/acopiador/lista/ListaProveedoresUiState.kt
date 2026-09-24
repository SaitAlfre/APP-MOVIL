package pe.ecolecta.presentation.acopiador.lista

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.acopio.AvanceDelDia
import pe.ecolecta.domain.acopio.CicloAcopio
import pe.ecolecta.domain.acopio.EstadoRecojo
import pe.ecolecta.domain.acopio.FilaAcopio
import pe.ecolecta.domain.acopio.avanceDelDia

enum class FiltroLista(val etiqueta: String) {
    TODOS("Todos"),
    PENDIENTES("Pendientes"),
    REGISTRADOS("Registrados"),
    SIN_RECOJO("Sin recojo"),
}

/** Dos vistas de la misma información: la lista de trabajo de hoy, o la hoja del ciclo en tabla. */
enum class ModoLista { HOY, CICLO }

data class ListaProveedoresUiState(
    val cargando: Boolean = true,
    val modo: ModoLista = ModoLista.HOY,
    val ciclo: CicloAcopio? = null,
    val hoy: LocalDate? = null,
    val zonaNombre: String = "",
    val filas: List<FilaAcopio> = emptyList(),
    val busqueda: String = "",
    val filtro: FiltroLista = FiltroLista.TODOS,
    val jornadaId: String? = null,
    val jornadaAbierta: Boolean = false,
    /** false: este celular no tiene sincronización remota; todo queda "guardado en este celular". */
    val remotoConfigurado: Boolean = false,
    /** Proveedor cuyo detalle del ciclo está abierto (nunca edita entregas: solo las muestra). */
    val detalleProveedorId: String? = null,
    val procesando: Boolean = false,
    val error: String? = null,
) {
    val avance: AvanceDelDia get() = hoy?.let { avanceDelDia(filas, it) } ?: AvanceDelDia(filas.size, 0, filas.size, 0)

    /** "25 asignados · 8 registrados · 14 pendientes · 3 sin recojo" */
    val resumen: String
        get() = with(avance) { "$asignados asignados · $registrados registrados · $pendientes pendientes · $sinRecojo sin recojo" }

    val visibles: List<FilaAcopio> get() = hoy?.let { filtrarFilas(filas, it, busqueda, filtro) } ?: emptyList()

    val detalle: FilaAcopio? get() = filas.firstOrNull { it.proveedor.id == detalleProveedorId }

    val totalCicloL: Double get() = filas.sumOf { it.totalCicloLitros }
}

fun estadoDeHoy(fila: FilaAcopio, hoy: LocalDate): EstadoRecojo = fila.dia(hoy)?.estado ?: EstadoRecojo.PENDIENTE

/** Búsqueda por nombre completo o código, y filtro por el estado del recojo de [hoy]. */
fun filtrarFilas(filas: List<FilaAcopio>, hoy: LocalDate, busqueda: String, filtro: FiltroLista): List<FilaAcopio> {
    val texto = busqueda.trim().lowercase()
    return filas
        .filter { fila ->
            texto.isBlank() ||
                fila.proveedor.nombres.lowercase().contains(texto) ||
                fila.proveedor.codigo.lowercase().contains(texto) ||
                fila.proveedor.dueno?.lowercase()?.contains(texto) == true
        }
        .filter { fila ->
            when (filtro) {
                FiltroLista.TODOS -> true
                FiltroLista.PENDIENTES -> estadoDeHoy(fila, hoy) == EstadoRecojo.PENDIENTE
                FiltroLista.REGISTRADOS -> estadoDeHoy(fila, hoy) == EstadoRecojo.REGISTRADO
                FiltroLista.SIN_RECOJO -> estadoDeHoy(fila, hoy) == EstadoRecojo.SIN_RECOJO
            }
        }
}
