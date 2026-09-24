package pe.ecolecta.domain.usecase.sync

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import pe.ecolecta.domain.acopio.aCompartido
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.repository.AuditoriaRepository
import pe.ecolecta.domain.repository.EntregaParaServidor
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.JornadaRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinRecojoRepository
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository

data class ResultadoSincronizacion(val enviados: Int, val sinConexion: Int, val fallidos: Int) {
    val total: Int get() = enviados + sinConexion + fallidos
}

/**
 * Envía las entregas (nuevas, corregidas o anuladas) y los "sin recojo" que están pendientes o con error
 * en ESTE celular a los destinos configurados:
 *
 * - Panel web ([ServidorWebRepository]): fuente OFICIAL de entregas, la base que consulta el
 *   administrador. Solo entregas: el panel no tiene "sin recojo".
 * - Firestore ([RegistroAcopioRemotoRepository]): copia para el celular del proveedor ("Mi ciclo").
 *
 * Un registro pasa a SYNCED solo cuando TODOS los destinos configurados confirmaron la escritura — y
 * solo si no cambió mientras se enviaba, para no dar por sincronizada una corrección que no salió.
 *
 * - Sin conexión: el registro sigue PENDING (con el motivo en `sync_error`) y se reintenta después.
 * - Otro error (servidor rechazó el dato, cuenta sin sesión con el servidor, dispositivo sin vincular):
 *   queda en ERROR con el motivo visible, y también se reintenta.
 * - Cada destino usa el id del registro como clave, así que un reintento sobrescribe el mismo
 *   documento / la misma fila y nunca crea duplicados, aunque el primer envío sí hubiera llegado.
 * - Sin ningún destino configurado no hace nada: los datos quedan "guardados en este celular", que es
 *   lo que la interfaz muestra.
 * - Los conflictos (CONFLICT) no se tocan: los resuelve el ADMIN.
 *
 * Es `single`: el [Mutex] evita dos envíos simultáneos del mismo registro desde el ciclo periódico y
 * desde un "Sincronizar ahora".
 */
class SincronizarRegistrosAcopioUseCase(
    private val entregaRepository: EntregaRepository,
    private val sinRecojoRepository: SinRecojoRepository,
    private val proveedorRepository: ProveedorRepository,
    private val usuarioRepository: UsuarioRepository,
    private val remoto: RegistroAcopioRemotoRepository,
    private val servidor: ServidorWebRepository,
    private val preparador: PreparadorEntregaServidor,
) {
    private val mutex = Mutex()

    val configurado: Boolean get() = remoto.configurado || servidor.configurado

    /** El panel web (lo que ve el administrador) está configurado en esta compilación. */
    val servidorConfigurado: Boolean get() = servidor.configurado

    suspend operator fun invoke(): ResultadoSincronizacion {
        if (!configurado) return ResultadoSincronizacion(0, 0, 0)
        return mutex.withLock { sincronizar() }
    }

    private suspend fun sincronizar(): ResultadoSincronizacion {
        var enviados = 0
        var sinConexion = 0
        var fallidos = 0
        val codigos = mutableMapOf<String, String?>()
        val nombres = mutableMapOf<String, String>()

        suspend fun codigoDe(proveedorId: String) =
            codigos.getOrPut(proveedorId) { proveedorRepository.obtenerPorId(proveedorId)?.codigo }

        suspend fun nombreDe(usuarioId: String) =
            nombres.getOrPut(usuarioId) { usuarioRepository.obtenerPorId(usuarioId)?.nombres ?: "Acopiador" }

        suspend fun enviar(
            proveedorId: String,
            usuarioId: String,
            publicar: suspend (codigo: String, nombre: String) -> Result<Unit>,
            alExito: suspend () -> Unit,
            alFallar: suspend (mensaje: String, definitivo: Boolean) -> Unit,
        ) {
            val codigo = codigoDe(proveedorId)
            if (codigo == null) {
                alFallar("Proveedor no encontrado en este celular.", true)
                fallidos++
                return
            }
            val resultado = try {
                publicar(codigo, nombreDe(usuarioId))
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                Result.failure(e)
            }
            resultado.fold(
                onSuccess = { alExito(); enviados++ },
                onFailure = { error ->
                    val faltaConexion = error is SinConexionRemotaException ||
                        (error is EnvioParcialException && error.cause is SinConexionRemotaException)
                    if (faltaConexion) {
                        alFallar(error.message ?: "Sin conexión", false)
                        sinConexion++
                    } else {
                        alFallar(error.message ?: "El servidor rechazó el envío.", true)
                        fallidos++
                    }
                },
            )
        }

        for (entrega in entregaRepository.pendientesDeSincronizar()) {
            enviar(
                proveedorId = entrega.proveedorId,
                usuarioId = entrega.usuarioId,
                publicar = { codigo, nombre -> publicarEntrega(entrega, codigo, nombre) },
                alExito = { entregaRepository.marcarSincronizada(entrega.id, entrega.updatedAt) },
                alFallar = { mensaje, definitivo ->
                    entregaRepository.registrarFalloSync(entrega.id, entrega.updatedAt, mensaje, definitivo)
                },
            )
        }

        // "Sin recojo" solo viaja al celular del proveedor: sin Firestore se queda guardado aquí.
        if (remoto.configurado) for (marca in sinRecojoRepository.pendientesDeSincronizar()) {
            enviar(
                proveedorId = marca.proveedorId,
                usuarioId = marca.usuarioId,
                publicar = { codigo, nombre -> remoto.publicar(marca.aCompartido(codigo, nombre)) },
                alExito = { sinRecojoRepository.marcarSincronizada(marca.id, marca.updatedAt) },
                alFallar = { mensaje, definitivo ->
                    sinRecojoRepository.registrarFalloSync(marca.id, marca.updatedAt, mensaje, definitivo)
                },
            )
        }

        return ResultadoSincronizacion(enviados, sinConexion, fallidos)
    }

    /** Primero el panel (oficial); si falla no se sigue, y el reintento reenvía ambos sin duplicar. */
    private suspend fun publicarEntrega(entrega: Entrega, codigo: String, nombre: String): Result<Unit> {
        if (servidor.configurado) {
            val datos = try {
                preparador.preparar(entrega)
            } catch (e: CancellationException) {
                throw e
            } catch (e: Exception) {
                return Result.failure(e)
            }
            servidor.enviarEntrega(entrega.usuarioId, datos).onFailure { return Result.failure(it) }
        }
        if (!remoto.configurado) return Result.success(Unit)
        return remoto.publicar(entrega.aCompartido(codigo, nombre)).recoverCatching { error ->
            // El panel ya la tiene pero el celular del proveedor no: no se da por sincronizada y el
            // motivo lo dice, para no aparentar que todos los destinos la recibieron.
            throw if (servidor.configurado) EnvioParcialException(error) else error
        }
    }
}

/** El panel web confirmó la entrega, pero la copia para el celular del proveedor (Firestore) falló. */
class EnvioParcialException(causa: Throwable) : Exception(
    "Recibida por el panel web; falta la copia para el celular del proveedor: ${causa.message ?: "error desconocido"}",
    causa,
)

/** Traduce una entrega local a lo que entiende el panel web. */
fun interface PreparadorEntregaServidor {
    suspend fun preparar(entrega: Entrega): EntregaParaServidor
}

/**
 * Resuelve en este celular el código del proveedor, el nombre de la zona, la placa, el usuario del
 * acopiador y los datos de su jornada. Si algo falta localmente, falla con un mensaje claro (el registro
 * queda con error visible) en vez de enviar datos incompletos.
 */
class PreparadorEntregaServidorLocal(
    private val proveedores: ProveedorRepository,
    private val usuarios: UsuarioRepository,
    private val jornadas: JornadaRepository,
    private val zonas: ZonaRepository,
    private val vehiculos: VehiculoRepository,
    private val auditoria: AuditoriaRepository,
) : PreparadorEntregaServidor {
    override suspend fun preparar(entrega: Entrega): EntregaParaServidor {
        val proveedor = proveedores.obtenerPorId(entrega.proveedorId) ?: error("Proveedor no encontrado en este celular.")
        val usuario = usuarios.obtenerPorId(entrega.usuarioId) ?: error("Acopiador no encontrado en este celular.")
        val jornada = jornadas.obtenerPorId(entrega.jornadaId) ?: error("Jornada no encontrada en este celular.")
        val zona = zonas.obtenerPorId(entrega.zonaId) ?: error("Zona no encontrada en este celular.")
        val vehiculo = vehiculos.obtenerPorId(entrega.vehiculoId) ?: error("Vehículo no encontrado en este celular.")
        val motivo = auditoria.filtrar(entidad = "entrega")
            .filter { it.entidadId == entrega.id && it.accion in setOf(AccionAuditoria.CORREGIR, AccionAuditoria.ANULAR) }
            .maxByOrNull { it.ocurridoEn }
            ?.motivo
        return EntregaParaServidor(
            id = entrega.id,
            jornadaId = jornada.id,
            jornadaAbiertaEn = jornada.abiertaEn,
            jornadaCerradaEn = jornada.cerradaEn,
            proveedorCodigo = proveedor.codigo,
            zonaNombre = zona.nombre,
            vehiculoPlaca = vehiculo.placa,
            acopiadorUsername = usuario.username,
            registradoEn = entrega.registradoEn,
            litros = entrega.litros,
            tachos = entrega.tachos,
            observaciones = entrega.observaciones,
            anulada = entrega.anulada,
            motivo = motivo,
            actualizadoEn = entrega.updatedAt,
        )
    }
}
