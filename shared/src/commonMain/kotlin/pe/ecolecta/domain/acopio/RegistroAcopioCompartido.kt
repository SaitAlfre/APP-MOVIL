package pe.ecolecta.domain.acopio

import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.ModalidadEntrega
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState

enum class TipoRegistroCompartido { ENTREGA, SIN_RECOJO }

/**
 * Lo que viaja entre celulares. Los ids locales de proveedor son distintos en cada dispositivo, así
 * que el proveedor se identifica por su [proveedorCodigo] (único, p. ej. "PRV-FAON-01"). No lleva
 * nombres ni datos de otros proveedores: solo el código del dueño del registro.
 */
data class RegistroAcopioCompartido(
    val id: String,
    val tipo: TipoRegistroCompartido,
    val proveedorCodigo: String,
    val zonaId: String,
    val jornadaId: String,
    val acopiadorId: String,
    val acopiadorNombre: String,
    /** Fecha de acopio en Perú, "yyyy-MM-dd". */
    val fecha: String,
    val registradoEn: Long,
    val litros: Double? = null,
    val tachos: Int? = null,
    val anulada: Boolean = false,
    val motivo: String? = null,
    val detalle: String? = null,
    val deshecha: Boolean = false,
    /** Reloj lógico: el servidor rechaza escrituras más viejas que la ya guardada. */
    val actualizadoEn: Long,
)

fun Entrega.aCompartido(proveedorCodigo: String, acopiadorNombre: String) = RegistroAcopioCompartido(
    id = id,
    tipo = TipoRegistroCompartido.ENTREGA,
    proveedorCodigo = proveedorCodigo,
    zonaId = zonaId,
    jornadaId = jornadaId,
    acopiadorId = usuarioId,
    acopiadorNombre = acopiadorNombre,
    fecha = fechaAcopioDe(registradoEn).toString(),
    registradoEn = registradoEn,
    litros = litros,
    tachos = tachos,
    anulada = anulada,
    actualizadoEn = updatedAt,
)

fun MarcaSinRecojo.aCompartido(proveedorCodigo: String, acopiadorNombre: String) = RegistroAcopioCompartido(
    id = id,
    tipo = TipoRegistroCompartido.SIN_RECOJO,
    proveedorCodigo = proveedorCodigo,
    zonaId = zonaId,
    jornadaId = jornadaId,
    acopiadorId = usuarioId,
    acopiadorNombre = acopiadorNombre,
    fecha = fecha.toString(),
    registradoEn = registradaEn,
    motivo = motivo.name,
    detalle = detalle,
    deshecha = deshecha,
    actualizadoEn = updatedAt,
)

/** Datos que ve el proveedor: sus entregas y marcas, ya combinando lo local con lo recibido. */
data class RegistrosProveedor(
    val entregas: List<Entrega>,
    val recojos: List<RecojoDelDia>,
    val sinRecojos: List<SinRecojoDelDia>,
    /** acopiadorId -> nombre, para mostrar quién registró cada entrega recibida. */
    val acopiadores: Map<String, String>,
)

/**
 * Combina lo que hay en este celular con lo recibido del servidor, SOLO para [proveedor]:
 * - todo lo que no sea de su ficha (otro id local u otro código) se descarta aquí aunque llegue;
 * - si un registro existe en ambos lados, manda la copia del servidor (es la confirmada);
 * - un registro solo local conserva su estado real de sincronización (nunca se muestra confirmado).
 */
fun combinarRegistrosProveedor(
    proveedor: Proveedor,
    entregasLocales: List<Entrega>,
    marcasLocales: List<MarcaSinRecojo>,
    recibidos: List<RegistroAcopioCompartido>,
    remotoDisponible: Boolean,
): RegistrosProveedor {
    val propios = recibidos.filter { it.proveedorCodigo == proveedor.codigo }
    val entregasRecibidas = propios.filter { it.tipo == TipoRegistroCompartido.ENTREGA && it.litros != null }
    val marcasRecibidas = propios.filter { it.tipo == TipoRegistroCompartido.SIN_RECOJO }
    val idsRecibidos = propios.map { it.id }.toSet()

    val entregas = entregasLocales
        .filter { it.proveedorId == proveedor.id && it.id !in idsRecibidos }
        .plus(entregasRecibidas.map { it.aEntrega(proveedor.id) })
        .sortedByDescending { it.registradoEn }

    val recojos = entregasLocales
        .filter { it.proveedorId == proveedor.id && it.id !in idsRecibidos }
        .map { it.aRecojoDelDia(remotoDisponible) } +
        entregasRecibidas.map {
            RecojoDelDia(
                id = it.id,
                fecha = LocalDate.parse(it.fecha),
                litros = it.litros ?: 0.0,
                tachos = it.tachos ?: 0,
                registradoEn = it.registradoEn,
                sincronizacion = EstadoSincronizacion.SINCRONIZADO,
                anulada = it.anulada,
            )
        }

    val sinRecojos = marcasLocales
        .filter { it.proveedorId == proveedor.id && it.vigente && it.id !in idsRecibidos }
        .map { it.aSinRecojoDelDia(remotoDisponible) } +
        marcasRecibidas.filterNot { it.deshecha }.map {
            SinRecojoDelDia(
                id = it.id,
                fecha = LocalDate.parse(it.fecha),
                motivo = MotivoSinRecojo.desde(it.motivo.orEmpty()),
                detalle = it.detalle,
                registradaEn = it.registradoEn,
                sincronizacion = EstadoSincronizacion.SINCRONIZADO,
            )
        }

    return RegistrosProveedor(
        entregas = entregas,
        recojos = recojos,
        sinRecojos = sinRecojos,
        acopiadores = propios.associate { it.acopiadorId to it.acopiadorNombre },
    )
}

private fun RegistroAcopioCompartido.aEntrega(proveedorIdLocal: String) = Entrega(
    id = id,
    jornadaId = jornadaId,
    proveedorId = proveedorIdLocal,
    usuarioId = acopiadorId,
    zonaId = zonaId,
    vehiculoId = "",
    litros = litros ?: 0.0,
    tachos = tachos ?: 0,
    modalidad = ModalidadEntrega.MEDIANTE_ACOPIADOR,
    observaciones = null,
    registradoEn = registradoEn,
    deviceId = "remoto",
    loteId = null,
    anulada = anulada,
    syncState = SyncState.SYNCED,
    syncError = null,
    intentos = 0,
    updatedAt = actualizadoEn,
)
