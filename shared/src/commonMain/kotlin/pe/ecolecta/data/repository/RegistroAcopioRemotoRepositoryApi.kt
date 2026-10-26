package pe.ecolecta.data.repository

import kotlinx.coroutines.flow.*
import kotlinx.serialization.json.*
import pe.ecolecta.domain.acopio.*
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.usecase.sync.EstadoSincronizacion

/** Un único servidor: envía las marcas y expone la copia local confirmada por el coordinador. */
class RegistroAcopioRemotoRepositoryApi(
    private val servidor: ServidorWebRepository,
    private val sesiones: SesionRepository,
    private val jornadas: JornadaRepository,
    private val zonas: ZonaRepository,
    private val vehiculos: VehiculoRepository,
    private val usuarios: UsuarioRepository,
    private val proveedores: ProveedorRepository,
    private val entregas: EntregaRepository,
    private val marcas: SinRecojoRepository,
    private val estado: () -> StateFlow<EstadoSincronizacion>,
) : RegistroAcopioRemotoRepository {
    override val configurado: Boolean get() = servidor.configurado

    override suspend fun publicar(registro: RegistroAcopioCompartido): Result<Unit> {
        if (registro.tipo != TipoRegistroCompartido.SIN_RECOJO) {
            return Result.failure(IllegalArgumentException("Las entregas se envían por su API oficial."))
        }
        return try {
            val jornada = jornadas.obtenerPorId(registro.jornadaId) ?: error("No se encontró la jornada del recojo.")
            val zona = zonas.obtenerPorId(jornada.zonaId) ?: error("No se encontró la zona.")
            val vehiculo = vehiculos.obtenerPorId(jornada.vehiculoId) ?: error("No se encontró el vehículo.")
            servidor.enviarCambio(registro.acopiadorId, "sin_recojo", buildJsonObject {
                put("uuid", registro.id); put("jornadaId", jornada.id)
                put("proveedorCodigo", registro.proveedorCodigo); put("zonaNombre", zona.nombre)
                put("vehiculoPlaca", vehiculo.placa); put("abiertaEn", jornada.abiertaEn)
                put("cerradaEn", jornada.cerradaEn?.let(::JsonPrimitive) ?: JsonNull)
                put("fecha", registro.fecha); put("motivo", registro.motivo)
                put("detalle", registro.detalle); put("registradoEn", registro.registradoEn)
                put("deshecha", registro.deshecha)
                put("deshechaEn", if (registro.deshecha) JsonPrimitive(registro.actualizadoEn) else JsonNull)
                put("actualizadoEn", registro.actualizadoEn)
            }).map { }
        } catch (e: kotlinx.coroutines.CancellationException) {
            throw e
        } catch (e: Exception) { Result.failure(e) }
    }

    override fun observarDeProveedor(proveedorCodigo: String): Flow<EventoRegistrosRemotos> = flow {
        val sesion = sesiones.observar().first()
        val proveedor = proveedores.obtenerPorCodigo(proveedorCodigo)
        if (sesion?.rolActivo != Rol.PROVEEDOR || proveedor == null || proveedor.usuarioId != sesion.usuario.id) {
            emit(EventoRegistrosRemotos.NoDisponible("No tienes acceso a esta ficha de proveedor."))
            return@flow
        }
        emitAll(combine(entregas.observarPorProveedor(proveedor.id), marcas.observarPorProveedor(proveedor.id),
            usuarios.observarTodos(), estado()) { filas, sinRecojos, cuentas, sync ->
            when {
                sync.sinConexion -> EventoRegistrosRemotos.SinConexion
                sync.requiereEnlace -> EventoRegistrosRemotos.NoDisponible("Confirma tu acceso para recibir las novedades.")
                else -> {
                    val nombres = cuentas.associate { it.id to it.nombres }
                    val recibidos = filas.filter { it.syncState == SyncState.SYNCED }
                        .map { it.aCompartido(proveedorCodigo, nombres[it.usuarioId] ?: "Acopiador") } +
                        sinRecojos.filter { it.syncState == SyncState.SYNCED }
                            .map { it.aCompartido(proveedorCodigo, nombres[it.usuarioId] ?: "Acopiador") }
                    EventoRegistrosRemotos.Recibidos(recibidos, desdeCache = !sync.verificado)
                }
            }
        })
    }
}
