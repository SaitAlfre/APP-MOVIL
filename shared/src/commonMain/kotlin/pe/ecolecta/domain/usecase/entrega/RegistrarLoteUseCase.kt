package pe.ecolecta.domain.usecase.entrega

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.EntregaInvalidaException
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.ProveedorRepository

data class ItemLote(val proveedorId: String, val litros: Double, val tachos: Int, val observaciones: String? = null)

/** Todas las entregas del lote comparten un lote_id y se guardan en una única transacción: si una falla, ninguna queda (§26). */
class RegistrarLoteUseCase(
    private val entregaRepository: EntregaRepository,
    private val proveedorRepository: ProveedorRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(
        jornadaId: String,
        usuarioId: String,
        zonaId: String,
        vehiculoId: String,
        items: List<ItemLote>,
    ): Result<List<Entrega>> {
        if (items.isEmpty()) return Result.failure(EntregaInvalidaException.LoteVacio)

        val loteId = nuevoId()
        val ahora = reloj.ahora().toEpochMilliseconds()
        val deviceId = deviceIdProvider.obtenerId()

        val entregas = mutableListOf<Entrega>()
        val auditorias = mutableListOf<Auditoria>()

        for (item in items) {
            val proveedor = proveedorRepository.obtenerPorId(item.proveedorId)
                ?: return Result.failure(IllegalStateException("Proveedor no encontrado"))
            if (item.litros > proveedor.capacidadTotalL) {
                return Result.failure(EntregaInvalidaException.SuperaCapacidad(proveedor.capacidadTotalL))
            }

            val entrega = Entrega.crear(
                id = nuevoId(),
                jornadaId = jornadaId,
                proveedorId = item.proveedorId,
                usuarioId = usuarioId,
                zonaId = zonaId,
                vehiculoId = vehiculoId,
                litros = item.litros,
                tachos = item.tachos,
                observaciones = item.observaciones,
                registradoEn = ahora,
                deviceId = deviceId,
                loteId = loteId,
            ).getOrElse { return Result.failure(it) }

            entregas += entrega
            auditorias += Auditoria(
                id = nuevoId(),
                entidad = "entrega",
                entidadId = entrega.id,
                accion = AccionAuditoria.CREAR,
                valorAntes = null,
                valorDespues = "litros=${item.litros};tachos=${item.tachos}",
                motivo = null,
                usuarioId = usuarioId,
                ocurridoEn = ahora,
                deviceId = deviceId,
                syncState = SyncState.PENDING,
            )
        }

        return runCatching {
            entregaRepository.registrarLote(entregas, auditorias)
            entregas
        }
    }
}
