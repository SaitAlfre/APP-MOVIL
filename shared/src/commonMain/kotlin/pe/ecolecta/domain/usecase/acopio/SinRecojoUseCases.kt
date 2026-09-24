package pe.ecolecta.domain.usecase.acopio

import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.acopio.MarcaSinRecojo
import pe.ecolecta.domain.acopio.MotivoSinRecojo
import pe.ecolecta.domain.acopio.fechaAcopioDe
import pe.ecolecta.domain.acopio.hoyAcopio
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.nuevoId
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.SinRecojoRepository

sealed class SinRecojoException(mensaje: String) : Exception(mensaje) {
    data object JornadaNoEncontrada : SinRecojoException("La jornada no existe.")
    data object JornadaNoPertenece : SinRecojoException("La jornada pertenece a otro acopiador.")
    data object JornadaCerrada : SinRecojoException(
        "La jornada ya está cerrada. Para corregirla, reábrela desde el inicio (queda registrado en la auditoría).",
    )
    data object ProveedorFueraDeZona : SinRecojoException("El proveedor no está asignado a la zona de tu jornada.")
    data object YaTieneEntrega : SinRecojoException("Este proveedor ya tiene una entrega registrada hoy.")
    data object YaMarcado : SinRecojoException("Este proveedor ya está marcado como sin recojo hoy.")
    data object MarcaNoEncontrada : SinRecojoException("No se encontró la marca de sin recojo.")
}

private const val ENTIDAD = "sin_recojo"

/**
 * Marca explícitamente "Sin recojo" para un proveedor HOY (fecha de Perú), con un motivo obligatorio.
 * Solo con la jornada abierta del propio acopiador y para proveedores activos de su zona. Se guarda
 * con su auditoría en una transacción y queda pendiente de sincronizar.
 */
class MarcarSinRecojoUseCase(
    private val jornadaRepository: JornadaRepository,
    private val proveedorRepository: ProveedorRepository,
    private val entregaRepository: EntregaRepository,
    private val sinRecojoRepository: SinRecojoRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(
        jornadaId: String,
        proveedorId: String,
        usuarioId: String,
        motivo: MotivoSinRecojo,
        detalle: String?,
    ): Result<MarcaSinRecojo> = runCatching {
        val jornada = jornadaRepository.obtenerPorId(jornadaId) ?: throw SinRecojoException.JornadaNoEncontrada
        if (jornada.usuarioId != usuarioId) throw SinRecojoException.JornadaNoPertenece
        if (!jornada.estaAbierta) throw SinRecojoException.JornadaCerrada

        val proveedor = proveedorRepository.obtenerPorId(proveedorId)
        if (proveedor == null || proveedor.zonaId != jornada.zonaId || proveedor.estado != EstadoProveedor.ACTIVO) {
            throw SinRecojoException.ProveedorFueraDeZona
        }

        val hoy = hoyAcopio(reloj)
        val tieneEntregaHoy = entregaRepository.filtrar(proveedorId = proveedorId)
            .any { !it.anulada && fechaAcopioDe(it.registradoEn) == hoy }
        if (tieneEntregaHoy) throw SinRecojoException.YaTieneEntrega
        if (sinRecojoRepository.vigentePara(proveedorId, hoy) != null) throw SinRecojoException.YaMarcado

        val ahora = reloj.ahora().toEpochMilliseconds()
        val deviceId = deviceIdProvider.obtenerId()
        val marca = MarcaSinRecojo(
            id = nuevoId(),
            jornadaId = jornada.id,
            proveedorId = proveedorId,
            usuarioId = usuarioId,
            zonaId = jornada.zonaId,
            fecha = hoy,
            motivo = motivo,
            detalle = detalle?.trim()?.ifBlank { null },
            registradaEn = ahora,
            deshecha = false,
            deshechaEn = null,
            syncState = SyncState.PENDING,
            syncError = null,
            intentos = 0,
            updatedAt = ahora,
        )
        sinRecojoRepository.marcar(
            marca,
            Auditoria(
                id = nuevoId(),
                entidad = ENTIDAD,
                entidadId = marca.id,
                accion = AccionAuditoria.CREAR,
                valorAntes = null,
                valorDespues = "proveedorId=$proveedorId;fecha=$hoy;motivo=${motivo.name}",
                motivo = marca.textoMotivo,
                usuarioId = usuarioId,
                ocurridoEn = ahora,
                deviceId = deviceId,
                syncState = SyncState.PENDING,
            ),
        )
        marca
    }
}

/**
 * Deshace un "Sin recojo" mientras la jornada sigue abierta. La marca no se borra: queda deshecha,
 * auditada y pendiente de sincronizar para que el proveedor también deje de verla. Con la jornada
 * cerrada se rechaza: la corrección pasa por reabrirla ([pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase]).
 */
class DeshacerSinRecojoUseCase(
    private val jornadaRepository: JornadaRepository,
    private val sinRecojoRepository: SinRecojoRepository,
    private val reloj: Reloj,
    private val deviceIdProvider: DeviceIdProvider,
) {
    suspend operator fun invoke(marcaId: String, usuarioId: String): Result<Unit> = runCatching {
        val marca = sinRecojoRepository.obtenerPorId(marcaId) ?: throw SinRecojoException.MarcaNoEncontrada
        if (marca.deshecha) return@runCatching
        val jornada = jornadaRepository.obtenerPorId(marca.jornadaId) ?: throw SinRecojoException.JornadaNoEncontrada
        if (jornada.usuarioId != usuarioId) throw SinRecojoException.JornadaNoPertenece
        if (!jornada.estaAbierta) throw SinRecojoException.JornadaCerrada

        val ahora = reloj.ahora().toEpochMilliseconds()
        sinRecojoRepository.deshacer(
            id = marcaId,
            deshechaEn = ahora,
            auditoria = Auditoria(
                id = nuevoId(),
                entidad = ENTIDAD,
                entidadId = marcaId,
                accion = AccionAuditoria.ANULAR,
                valorAntes = "motivo=${marca.motivo.name};deshecha=false",
                valorDespues = "deshecha=true",
                motivo = "Sin recojo deshecho por el acopiador",
                usuarioId = usuarioId,
                ocurridoEn = ahora,
                deviceId = deviceIdProvider.obtenerId(),
                syncState = SyncState.PENDING,
            ),
        )
    }
}
