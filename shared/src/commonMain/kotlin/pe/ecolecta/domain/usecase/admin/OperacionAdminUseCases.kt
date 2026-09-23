package pe.ecolecta.domain.usecase.admin

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOn
import kotlinx.coroutines.flow.combine
import kotlinx.coroutines.flow.first
import kotlinx.datetime.DatePeriod
import kotlinx.datetime.LocalDate
import kotlinx.datetime.TimeZone
import kotlinx.datetime.plus
import kotlinx.datetime.toLocalDateTime
import kotlin.math.roundToLong
import kotlin.time.Instant
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.AlertaAdmin
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Comunicado
import pe.ecolecta.domain.model.ControlCalidad
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.EstadoLiquidacion
import pe.ecolecta.domain.model.EstadoSolicitud
import pe.ecolecta.domain.model.LiquidacionSemanal
import pe.ecolecta.domain.model.PagoProveedor
import pe.ecolecta.domain.model.SolicitudProveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.TipoAlertaAdmin
import pe.ecolecta.domain.model.inicioSemanaProveedor
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.AlertaDescartadaRepository
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.ComunicadoRepository
import pe.ecolecta.domain.repository.ControlCalidadRepository
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.GestionPortalRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.TrasladoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.usecase.traslado.AutorizarTrasladoUseCase
import pe.ecolecta.domain.usecase.traslado.CrearTrasladoUseCase

/** La semana contable se calcula en Lima, igual que en el portal del proveedor. */
private val ZONA_LIMA = TimeZone.of("America/Lima")

/** Solo los análisis recientes piden atención; los antiguos quedan en el historial de calidad. */
private const val DIAS_ALERTA_CALIDAD = 14

fun fechaLima(epochMs: Long): LocalDate = Instant.fromEpochMilliseconds(epochMs).toLocalDateTime(ZONA_LIMA).date

private fun Double.redondeo2(): Double = (this * 100).roundToLong() / 100.0

private fun Double.texto(): String {
    val centesimos = (this * 100).roundToLong()
    return "${centesimos / 100}.${(centesimos % 100).toString().padStart(2, '0')}"
}

/**
 * Bandeja única de ADMIN: conflictos de sincronización, reclamos y traslados pedidos por los
 * proveedores, traslados pendientes y análisis de calidad recientes que no salieron aprobados.
 * Las alertas ocultadas por ADMIN se excluyen sin alterar el registro de origen.
 */
class ObservarAlertasAdminUseCase(
    private val entregas: EntregaRepository,
    private val portal: GestionPortalRepository,
    private val traslados: TrasladoRepository,
    private val calidad: ControlCalidadRepository,
    private val proveedores: ProveedorRepository,
    private val zonas: ZonaRepository,
    private val descartadas: AlertaDescartadaRepository,
    private val reloj: Reloj,
) {
    operator fun invoke(): Flow<List<AlertaAdmin>> {
        val fuentes = combine(
            entregas.observarConflictos(),
            portal.todasSolicitudes(),
            traslados.observarPendientes(),
            calidad.observarTodos(),
        ) { conflictos, solicitudes, pendientes, controles -> Fuentes(conflictos, solicitudes, pendientes, controles) }

        return combine(fuentes, proveedores.observarTodos(), zonas.observarTodas(), descartadas.observarIds()) { f, provs, zns, ocultas ->
            val nombre = { id: String -> provs.firstOrNull { it.id == id }?.nombres ?: "Proveedor no disponible" }
            val zona = { id: String? -> zns.firstOrNull { it.id == id }?.nombre ?: id ?: "zona no indicada" }
            val limiteCalidad = reloj.ahora().toEpochMilliseconds() - DIAS_ALERTA_CALIDAD * 86_400_000L

            val alertas = buildList {
                f.conflictos.filterNot { it.anulada }.forEach { e ->
                    add(
                        AlertaAdmin(
                            id = "conflicto:${e.id}",
                            tipo = TipoAlertaAdmin.CONFLICTO,
                            titulo = "Conflicto de sincronización",
                            descripcion = "${nombre(e.proveedorId)} — ${e.litros.texto()} L en el dispositivo" +
                                (e.litrosServidor?.let { " vs ${it.texto()} L en el servidor" } ?: ""),
                            ocurridaEn = e.updatedAt,
                            referenciaId = e.id,
                            proveedorId = e.proveedorId,
                            urgente = true,
                        ),
                    )
                }
                f.solicitudes.filter { EstadoSolicitud.estaPendiente(it.estado) }.forEach { s ->
                    val esTraslado = s.tipo == "TRASLADO"
                    add(
                        AlertaAdmin(
                            id = "solicitud:${s.id}",
                            tipo = if (esTraslado) TipoAlertaAdmin.TRASLADO else TipoAlertaAdmin.RECLAMO,
                            titulo = if (esTraslado) "Solicitud de traslado" else "Reclamo pendiente",
                            descripcion = if (esTraslado) {
                                "${nombre(s.proveedorId)} — de ${zona(provs.firstOrNull { it.id == s.proveedorId }?.zonaId)} a ${zona(s.referenciaId)}"
                            } else {
                                "${nombre(s.proveedorId)} — ${s.motivo}" + (s.litros?.let { " (${it.texto()} L)" } ?: "")
                            },
                            ocurridaEn = s.creadaEn,
                            referenciaId = s.id,
                            proveedorId = s.proveedorId,
                        ),
                    )
                }
                f.traslados.forEach { t ->
                    add(
                        AlertaAdmin(
                            id = "traslado:${t.id}",
                            tipo = TipoAlertaAdmin.TRASLADO,
                            titulo = "Traslado por autorizar",
                            descripcion = "${nombre(t.proveedorId)} — de ${zona(t.zonaOrigenId)} a ${zona(t.zonaDestinoId)}",
                            ocurridaEn = t.creadoEn,
                            referenciaId = t.id,
                            proveedorId = t.proveedorId,
                        ),
                    )
                }
                f.controles
                    .filter { !it.visita.ejemplo && it.estado != EstadoControlCalidad.APROBADO && it.registradoEn >= limiteCalidad }
                    .forEach { c ->
                        val proveedor = c.visita.proveedorNombre.ifBlank { nombre(c.proveedorId) }
                        val detalle = c.alertas.firstOrNull() ?: when (c.estado) {
                            EstadoControlCalidad.RECHAZADO -> "análisis rechazado"
                            EstadoControlCalidad.REPETIR -> "repetir análisis"
                            else -> "análisis con observaciones"
                        }
                        add(
                            AlertaAdmin(
                                id = "calidad:${c.id}",
                                tipo = TipoAlertaAdmin.CALIDAD,
                                titulo = "Calidad requiere revisión",
                                descripcion = "$proveedor — $detalle",
                                ocurridaEn = c.registradoEn,
                                referenciaId = c.id,
                                proveedorId = c.proveedorId,
                                urgente = c.estado == EstadoControlCalidad.RECHAZADO,
                            ),
                        )
                    }
            }
            alertas.filterNot { it.id in ocultas }.sortedByDescending { it.ocurridaEn }
        }.flowOn(Dispatchers.Default)
    }

    private data class Fuentes(
        val conflictos: List<Entrega>,
        val solicitudes: List<SolicitudProveedor>,
        val traslados: List<pe.ecolecta.domain.model.TrasladoZona>,
        val controles: List<ControlCalidad>,
    )
}

class OcultarAlertaUseCase(private val descartadas: AlertaDescartadaRepository, private val reloj: Reloj) {
    suspend operator fun invoke(alertaId: String, usuarioId: String): Result<Unit> = runCatching {
        descartadas.descartar(alertaId, usuarioId, reloj.ahora().toEpochMilliseconds())
    }
}

/**
 * Decide una solicitud del portal del proveedor. Aprobar un traslado lo registra y autoriza con
 * el flujo existente, que mueve al proveedor de zona y deja auditoría; el resto solo cambia el
 * estado visible para el proveedor.
 */
class ResolverSolicitudUseCase(
    private val portal: GestionPortalRepository,
    private val crearTraslado: CrearTrasladoUseCase,
    private val autorizarTraslado: AutorizarTrasladoUseCase,
) {
    suspend operator fun invoke(solicitudId: String, aprobar: Boolean, adminId: String): Result<Unit> = runCatching {
        val solicitud = portal.todasSolicitudes().first().firstOrNull { it.id == solicitudId }
            ?: error("La solicitud ya no existe en este dispositivo.")
        require(EstadoSolicitud.estaPendiente(solicitud.estado)) { "Esta solicitud ya fue resuelta." }
        if (aprobar && solicitud.tipo == "TRASLADO") {
            val destino = solicitud.referenciaId ?: error("La solicitud no indica zona de destino.")
            val traslado = crearTraslado(solicitud.proveedorId, destino, solicitud.descripcion).getOrThrow()
            autorizarTraslado(traslado.id, adminId).getOrThrow()
        }
        val nuevoEstado = when {
            !aprobar -> EstadoSolicitud.RECHAZADA
            solicitud.tipo == "TRASLADO" -> EstadoSolicitud.APROBADA
            else -> EstadoSolicitud.ATENDIDA
        }
        portal.actualizarSolicitud(solicitud.copy(estado = nuevoEstado))
    }
}

/** Semanas con entregas (más reciente primero) y el estado de su liquidación. */
class ObservarLiquidacionesUseCase(
    private val entregas: EntregaRepository,
    private val portal: GestionPortalRepository,
    private val reloj: Reloj,
) {
    operator fun invoke(semanas: Int = 8): Flow<List<LiquidacionSemanal>> =
        combine(entregas.observarConFiltros(), portal.todosPagos()) { lista, pagos ->
            val hoy = fechaLima(reloj.ahora().toEpochMilliseconds())
            val semanaActual = inicioSemanaProveedor(hoy)
            val porSemana = lista.filterNot { it.anulada }.groupBy { inicioSemanaProveedor(fechaLima(it.registradoEn)) }
            val desdes = (porSemana.keys + semanaActual).sortedDescending().take(semanas)
            desdes.map { desde ->
                val deLaSemana = porSemana[desde].orEmpty()
                val liquidados = pagos.filter { it.desde == desde.toString() && it.estado.uppercase() != "ANULADA" }
                val estado = when {
                    liquidados.isNotEmpty() && liquidados.all { it.estado.uppercase() in setOf("PAGADA", "PAGADO") } -> EstadoLiquidacion.PAGADA
                    liquidados.isNotEmpty() -> EstadoLiquidacion.APROBADA
                    desde >= semanaActual -> EstadoLiquidacion.EN_CURSO
                    else -> EstadoLiquidacion.POR_APROBAR
                }
                LiquidacionSemanal(
                    desde = desde,
                    litros = deLaSemana.sumOf { it.litros }.redondeo2(),
                    proveedores = deLaSemana.map { it.proveedorId }.distinct().size,
                    entregas = deLaSemana.size,
                    precio = liquidados.firstOrNull()?.precio,
                    total = liquidados.takeIf { it.isNotEmpty() }?.sumOf { it.total }?.redondeo2(),
                    estado = estado,
                )
            }
        }.flowOn(Dispatchers.Default)
}

/**
 * Aprueba la liquidación de una semana cerrada: genera una liquidación por proveedor (litros ×
 * precio) que el proveedor ve en "Mis pagos". Es la fuente autorizada que el portal espera.
 */
class AprobarLiquidacionUseCase(
    private val entregas: EntregaRepository,
    private val portal: GestionPortalRepository,
    private val auditoria: AuditoriaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(desde: LocalDate, precioPorLitro: Double, adminId: String): Result<Int> = runCatching {
        require(precioPorLitro.isFinite() && precioPorLitro > 0 && precioPorLitro <= 20) { "Ingresa un precio por litro entre S/ 0.01 y S/ 20.00." }
        val ahora = reloj.ahora().toEpochMilliseconds()
        require(desde < inicioSemanaProveedor(fechaLima(ahora))) { "Solo se liquidan semanas ya cerradas." }
        val existentes = portal.todosPagos().first().filter { it.desde == desde.toString() && it.estado.uppercase() != "ANULADA" }
        require(existentes.isEmpty()) { "Esta semana ya tiene una liquidación aprobada." }

        val hasta = desde.plus(DatePeriod(days = 6))
        val precio = precioPorLitro.redondeo2()
        val pagos = entregas.observarConFiltros().first()
            .filter { !it.anulada && inicioSemanaProveedor(fechaLima(it.registradoEn)) == desde }
            .groupBy { it.proveedorId }
            .map { (proveedorId, suyas) ->
                val litros = suyas.sumOf { it.litros }.redondeo2()
                val bruto = (litros * precio).redondeo2()
                PagoProveedor(
                    id = "liq-$desde-$proveedorId",
                    proveedorId = proveedorId,
                    desde = desde.toString(),
                    hasta = hasta.toString(),
                    litros = litros,
                    precio = precio,
                    bruto = bruto,
                    descuento = 0.0,
                    total = bruto,
                    estado = "APROBADA",
                )
            }
        require(pagos.isNotEmpty()) { "La semana no tiene entregas para liquidar." }
        portal.guardarPagos(pagos, ahora)
        auditoria.insertar(
            Auditoria(
                id = nuevoId(), entidad = "liquidacion", entidadId = desde.toString(), accion = AccionAuditoria.AUTORIZAR,
                valorAntes = null, valorDespues = "precio=$precio;proveedores=${pagos.size};total=${pagos.sumOf { it.total }.redondeo2()}",
                motivo = "Liquidación semanal aprobada", usuarioId = adminId, ocurridoEn = ahora,
                deviceId = deviceIdProvider.obtenerId(), syncState = SyncState.PENDING,
            ),
        )
        pagos.size
    }
}

class MarcarLiquidacionPagadaUseCase(
    private val portal: GestionPortalRepository,
    private val auditoria: AuditoriaRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(desde: LocalDate, adminId: String): Result<Unit> = runCatching {
        val ahora = reloj.ahora().toEpochMilliseconds()
        val pendientes = portal.todosPagos().first().filter { it.desde == desde.toString() && it.estado.uppercase() == "APROBADA" }
        require(pendientes.isNotEmpty()) { "No hay liquidaciones aprobadas por pagar en esta semana." }
        val fecha = fechaLima(ahora).toString()
        portal.guardarPagos(pendientes.map { it.copy(estado = "PAGADA", fechaPago = fecha) }, ahora)
        auditoria.insertar(
            Auditoria(
                id = nuevoId(), entidad = "liquidacion", entidadId = desde.toString(), accion = AccionAuditoria.ACTUALIZAR,
                valorAntes = "APROBADA", valorDespues = "PAGADA", motivo = "Liquidación marcada como pagada",
                usuarioId = adminId, ocurridoEn = ahora, deviceId = deviceIdProvider.obtenerId(), syncState = SyncState.PENDING,
            ),
        )
    }
}

class PublicarComunicadoUseCase(private val comunicados: ComunicadoRepository, private val reloj: Reloj) {
    suspend operator fun invoke(mensaje: String, autorId: String, autorNombre: String): Result<Unit> = runCatching {
        val texto = mensaje.trim()
        require(texto.length in 5..500) { "Escribe un comunicado de entre 5 y 500 caracteres." }
        comunicados.publicar(Comunicado(nuevoId(), texto, autorId, autorNombre, reloj.ahora().toEpochMilliseconds()))
    }
}

