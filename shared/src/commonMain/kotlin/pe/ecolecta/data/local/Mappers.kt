package pe.ecolecta.data.local

import kotlinx.datetime.LocalDate
import pe.ecolecta.db.Auditoria as AuditoriaFila
import pe.ecolecta.db.Entrega as EntregaFila
import pe.ecolecta.db.Jornada as JornadaFila
import pe.ecolecta.db.Proveedor as ProveedorFila
import pe.ecolecta.db.Traslado_zona as TrasladoZonaFila
import pe.ecolecta.db.Usuario as UsuarioFila
import pe.ecolecta.db.Vehiculo as VehiculoFila
import pe.ecolecta.db.Zona as ZonaFila
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.TrasladoZona
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona

internal fun UsuarioFila.aDominio(roles: List<Rol>): Usuario = Usuario(
    id = id,
    username = username,
    nombres = nombres,
    dni = dni,
    pinHash = pin_hash,
    pinSalt = pin_salt,
    activo = activo != 0L,
    roles = roles,
    updatedAt = updated_at,
    intentosFallidos = intentos_fallidos.toInt(),
    bloqueadoHasta = bloqueado_hasta,
)

internal fun ZonaFila.aDominio(): Zona = Zona(id = id, nombre = nombre, activo = activo != 0L)

internal fun VehiculoFila.aDominio(): Vehiculo = Vehiculo(id = id, nombre = nombre, placa = placa, activo = activo != 0L)

internal fun ProveedorFila.aDominio(): Proveedor = Proveedor(
    id = id,
    codigo = codigo,
    nombres = nombres,
    dni = dni,
    telefono = telefono,
    direccion = direccion,
    zonaId = zona_id,
    tachos = tachos.toInt(),
    capacidadTachoL = capacidad_tacho_l,
    estado = EstadoProveedor.valueOf(estado),
    updatedAt = updated_at,
    syncState = SyncState.valueOf(sync_state),
    usuarioId = usuario_id,
)

internal fun TrasladoZonaFila.aDominio(): TrasladoZona = TrasladoZona(
    id = id,
    proveedorId = proveedor_id,
    zonaOrigenId = zona_origen_id,
    zonaDestinoId = zona_destino_id,
    motivo = motivo,
    autorizadoPor = autorizado_por,
    estado = EstadoTraslado.valueOf(estado),
    creadoEn = creado_en,
)

internal fun JornadaFila.aDominio(): Jornada = Jornada(
    id = id,
    usuarioId = usuario_id,
    zonaId = zona_id,
    vehiculoId = vehiculo_id,
    fecha = LocalDate.parse(fecha),
    abiertaEn = abierta_en,
    cerradaEn = cerrada_en,
    syncState = SyncState.valueOf(sync_state),
)

internal fun EntregaFila.aDominio(): Entrega = Entrega(
    id = id,
    jornadaId = jornada_id,
    proveedorId = proveedor_id,
    usuarioId = usuario_id,
    zonaId = zona_id,
    vehiculoId = vehiculo_id,
    litros = litros,
    tachos = tachos.toInt(),
    observaciones = observaciones,
    registradoEn = registrado_en,
    deviceId = device_id,
    loteId = lote_id,
    anulada = anulada != 0L,
    syncState = SyncState.valueOf(sync_state),
    syncError = sync_error,
    intentos = intentos.toInt(),
    updatedAt = updated_at,
    litrosServidor = litros_servidor,
    tachosServidor = tachos_servidor,
    motivoConflicto = motivo_conflicto,
)

internal fun AuditoriaFila.aDominio(): Auditoria = Auditoria(
    id = id,
    entidad = entidad,
    entidadId = entidad_id,
    accion = AccionAuditoria.valueOf(accion),
    valorAntes = valor_antes,
    valorDespues = valor_despues,
    motivo = motivo,
    usuarioId = usuario_id,
    ocurridoEn = ocurrido_en,
    deviceId = device_id,
    syncState = SyncState.valueOf(sync_state),
)
