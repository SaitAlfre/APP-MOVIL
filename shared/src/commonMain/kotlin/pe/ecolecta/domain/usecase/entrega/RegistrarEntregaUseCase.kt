package pe.ecolecta.domain.usecase.entrega

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.ProveedorRepository

data class ResultadoRegistroEntrega(val entrega: Entrega, val advertenciaDesviacion: Boolean)

/**
 * Guardado offline (§25): nunca espera red. Bloquea si supera la capacidad física del tacho
 * (§24 validación 2; para el Acopiador esto es un límite duro, solo ADMIN puede forzarlo) y
 * marca una advertencia no bloqueante si la cantidad se desvía >40% del promedio de las
 * últimas 7 entregas del proveedor (§24 validación 3).
 */
class RegistrarEntregaUseCase(
    private val entregaRepository: EntregaRepository,
    private val proveedorRepository: ProveedorRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    companion object {
        const val UMBRAL_DESVIACION = 0.4
        const val ENTREGAS_PARA_PROMEDIO = 7
    }

    suspend operator fun invoke(
        jornadaId: String,
        proveedorId: String,
        usuarioId: String,
        zonaId: String,
        vehiculoId: String,
        litros: Double,
        tachos: Int,
        observaciones: String?,
        loteId: String? = null,
    ): Result<ResultadoRegistroEntrega> {
        val proveedor = proveedorRepository.obtenerPorId(proveedorId)
            ?: return Result.failure(IllegalStateException("Proveedor no encontrado"))

        if (proveedor.estado != EstadoProveedor.ACTIVO) {
            return Result.failure(EntregaInvalidaException.ProveedorNoActivo(proveedor.estado))
        }

        if (litros > proveedor.capacidadTotalL) {
            return Result.failure(EntregaInvalidaException.SuperaCapacidad(proveedor.capacidadTotalL))
        }

        val ahora = reloj.ahora().toEpochMilliseconds()
        val deviceId = deviceIdProvider.obtenerId()

        val entrega = Entrega.crear(
            id = nuevoId(),
            jornadaId = jornadaId,
            proveedorId = proveedorId,
            usuarioId = usuarioId,
            zonaId = zonaId,
            vehiculoId = vehiculoId,
            litros = litros,
            tachos = tachos,
            observaciones = observaciones,
            registradoEn = ahora,
            deviceId = deviceId,
            loteId = loteId,
        ).getOrElse { return Result.failure(it) }

        val anteriores = entregaRepository.filtrar(proveedorId = proveedorId).take(ENTREGAS_PARA_PROMEDIO)
        val promedio = if (anteriores.isNotEmpty()) anteriores.map { it.litros }.average() else 0.0
        val advertencia = promedio > 0.0 && kotlin.math.abs(litros - promedio) / promedio > UMBRAL_DESVIACION

        val auditoria = Auditoria(
            id = nuevoId(),
            entidad = "entrega",
            entidadId = entrega.id,
            accion = AccionAuditoria.CREAR,
            valorAntes = null,
            valorDespues = "litros=$litros;tachos=$tachos",
            motivo = null,
            usuarioId = usuarioId,
            ocurridoEn = ahora,
            deviceId = deviceId,
            syncState = SyncState.PENDING,
        )

        return runCatching {
            entregaRepository.registrar(entrega, auditoria)
            ResultadoRegistroEntrega(entrega, advertencia)
        }
    }
}
