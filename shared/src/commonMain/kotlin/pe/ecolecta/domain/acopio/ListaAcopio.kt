package pe.ecolecta.domain.acopio

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState
import kotlin.math.roundToLong

/** Estado del recojo de un proveedor en un día. La celda vacía es PENDIENTE, nunca "cero litros". */
enum class EstadoRecojo { PENDIENTE, REGISTRADO, SIN_RECOJO }

/** Dónde está el dato, independiente de [EstadoRecojo]. */
enum class EstadoSincronizacion(val etiqueta: String) {
    /** Este dispositivo no tiene sincronización remota configurada: el dato solo existe aquí. */
    EN_ESTE_CELULAR("Guardado en este celular"),
    PENDIENTE("Pendiente de sincronizar"),
    SINCRONIZADO("Sincronizado"),
    ERROR("Error al sincronizar"),
}

fun estadoSincronizacionDe(syncState: SyncState, remotoDisponible: Boolean): EstadoSincronizacion = when (syncState) {
    SyncState.SYNCED -> EstadoSincronizacion.SINCRONIZADO
    SyncState.ERROR, SyncState.CONFLICT -> EstadoSincronizacion.ERROR
    SyncState.PENDING, SyncState.SYNCING -> if (remotoDisponible) EstadoSincronizacion.PENDIENTE else EstadoSincronizacion.EN_ESTE_CELULAR
}

/** Una entrega individual dentro del día (nunca se fusiona con otras del mismo día). */
data class RecojoDelDia(
    val id: String,
    val fecha: LocalDate,
    val litros: Double,
    val tachos: Int,
    val registradoEn: Long,
    val sincronizacion: EstadoSincronizacion,
    val anulada: Boolean = false,
)

data class SinRecojoDelDia(
    val id: String,
    val fecha: LocalDate,
    val motivo: MotivoSinRecojo,
    val detalle: String?,
    val registradaEn: Long,
    val sincronizacion: EstadoSincronizacion,
) {
    val textoMotivo: String get() = listOfNotNull(motivo.etiqueta, detalle).joinToString(" · ")
}

/** Una celda Día N de la hoja: todas las entregas del día, su total y el "sin recojo" si lo hubo. */
data class DiaAcopio(
    val fecha: LocalDate,
    val recojos: List<RecojoDelDia>,
    val sinRecojo: SinRecojoDelDia?,
) {
    val totalLitros: Double get() = (recojos.sumOf { it.litros } * 100).roundToLong() / 100.0

    /** Una entrega registrada manda sobre una marca de "sin recojo" del mismo día. */
    val estado: EstadoRecojo
        get() = when {
            recojos.isNotEmpty() -> EstadoRecojo.REGISTRADO
            sinRecojo != null -> EstadoRecojo.SIN_RECOJO
            else -> EstadoRecojo.PENDIENTE
        }

    val horaUltima: Long? get() = recojos.maxOfOrNull { it.registradoEn }

    /** El peor estado entre lo registrado ese día; null si no hay nada registrado. */
    val sincronizacion: EstadoSincronizacion?
        get() {
            val estados = recojos.map { it.sincronizacion } + listOfNotNull(sinRecojo?.sincronizacion)
            return PRIORIDAD_SYNC.firstOrNull { it in estados }
        }

    private companion object {
        val PRIORIDAD_SYNC = listOf(
            EstadoSincronizacion.ERROR,
            EstadoSincronizacion.PENDIENTE,
            EstadoSincronizacion.EN_ESTE_CELULAR,
            EstadoSincronizacion.SINCRONIZADO,
        )
    }
}

/**
 * Reparte entregas y marcas en las 6 fechas de [ciclo]. Descarta anuladas, marcas deshechas y todo lo
 * que caiga fuera del ciclo; las entregas del día se ordenan por hora y se conservan una por una.
 */
fun construirDiasDelCiclo(
    ciclo: CicloAcopio,
    recojos: List<RecojoDelDia>,
    sinRecojos: List<SinRecojoDelDia>,
): List<DiaAcopio> {
    val porDia = recojos.filterNot { it.anulada }.groupBy { it.fecha }
    val marcasPorDia = sinRecojos.groupBy { it.fecha }
    return ciclo.dias.map { fecha ->
        DiaAcopio(
            fecha = fecha,
            recojos = porDia[fecha].orEmpty().sortedBy { it.registradoEn },
            // Si por un reintento hubiera dos marcas vigentes, se muestra la más reciente.
            sinRecojo = marcasPorDia[fecha]?.maxByOrNull { it.registradaEn },
        )
    }
}

/** Fila de la hoja del acopiador: Zona | Proveedor | Día 1 … Día 6. */
data class FilaAcopio(val proveedor: Proveedor, val dias: List<DiaAcopio>) {
    fun dia(fecha: LocalDate): DiaAcopio? = dias.firstOrNull { it.fecha == fecha }
    val totalCicloLitros: Double get() = (dias.sumOf { it.totalLitros } * 100).roundToLong() / 100.0
}

fun Entrega.aRecojoDelDia(remotoDisponible: Boolean) = RecojoDelDia(
    id = id,
    fecha = fechaAcopioDe(registradoEn),
    litros = litros,
    tachos = tachos,
    registradoEn = registradoEn,
    sincronizacion = estadoSincronizacionDe(syncState, remotoDisponible),
    anulada = anulada,
)

fun MarcaSinRecojo.aSinRecojoDelDia(remotoDisponible: Boolean) = SinRecojoDelDia(
    id = id,
    fecha = fecha,
    motivo = motivo,
    detalle = detalle,
    registradaEn = registradaEn,
    sincronizacion = estadoSincronizacionDe(syncState, remotoDisponible),
)

/**
 * Filas de la lista del acopiador: SOLO los proveedores activos asignados a la zona de su jornada
 * ([zonaId]), aunque el origen de datos traiga otros. Lo mismo para entregas y marcas.
 */
fun construirFilasAcopio(
    ciclo: CicloAcopio,
    zonaId: String,
    proveedores: List<Proveedor>,
    entregas: List<Entrega>,
    marcas: List<MarcaSinRecojo>,
    remotoDisponible: Boolean,
): List<FilaAcopio> {
    val asignados = proveedores
        .filter { it.zonaId == zonaId && it.estado == EstadoProveedor.ACTIVO }
        .sortedBy { it.nombres.lowercase() }
    val entregasPorProveedor = entregas.groupBy { it.proveedorId }
    val marcasPorProveedor = marcas.filter { it.vigente }.groupBy { it.proveedorId }
    return asignados.map { proveedor ->
        FilaAcopio(
            proveedor = proveedor,
            dias = construirDiasDelCiclo(
                ciclo = ciclo,
                recojos = entregasPorProveedor[proveedor.id].orEmpty().map { it.aRecojoDelDia(remotoDisponible) },
                sinRecojos = marcasPorProveedor[proveedor.id].orEmpty().map { it.aSinRecojoDelDia(remotoDisponible) },
            ),
        )
    }
}

/** Avance del día: "25 asignados · 8 registrados · 14 pendientes · 3 sin recojo". */
data class AvanceDelDia(val asignados: Int, val registrados: Int, val pendientes: Int, val sinRecojo: Int)

fun avanceDelDia(filas: List<FilaAcopio>, fecha: LocalDate): AvanceDelDia {
    val estados = filas.map { it.dia(fecha)?.estado ?: EstadoRecojo.PENDIENTE }
    return AvanceDelDia(
        asignados = filas.size,
        registrados = estados.count { it == EstadoRecojo.REGISTRADO },
        pendientes = estados.count { it == EstadoRecojo.PENDIENTE },
        sinRecojo = estados.count { it == EstadoRecojo.SIN_RECOJO },
    )
}
