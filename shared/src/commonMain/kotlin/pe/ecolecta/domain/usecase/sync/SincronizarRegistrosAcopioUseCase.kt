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
import pe.ecolecta.domain.repository.SinSesionServidorException
import pe.ecolecta.domain.repository.UsuarioRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository

/**
 * Resultado real de un envío. Cada registro cae en UNA categoría:
 * - [enviados]: todos los destinos configurados confirmaron.
 * - [sinConexion]: el servidor no respondió; se reintenta solo.
 * - [sinEnlazar]: la cuenta que debe enviarlo no tiene sesión con el panel en este celular. No es un error
 *   del servidor ni se arregla reintentando: [cuentasSinEnlazar] deben iniciar sesión con conexión.
 * - [parciales]: reservado para resultados anteriores; el envío actual usa un único destino.
 * - [fallidos]: el servidor respondió y NO aceptó el dato; el motivo queda en el registro.
 */
data class ResultadoSincronizacion(
    val enviados: Int,
    val sinConexion: Int,
    val fallidos: Int,
    val sinEnlazar: Int = 0,
    val parciales: Int = 0,
    val cuentasSinEnlazar: Set<String> = emptySet(),
    val motivoSinConexion: String? = null,
) {
    val total: Int get() = enviados + sinConexion + fallidos + sinEnlazar + parciales
}

/** Envía los registros locales al servidor oficial. Cada confirmación conserva el mismo id y
 * solo marca la versión enviada. Sin conexión se conserva la cola; los conflictos requieren revisión.
 * El canal alternativo se conserva únicamente para repositorios locales de pruebas sin servidor.
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

    suspend operator fun invoke(usuarioId: String? = null): ResultadoSincronizacion {
        if (!configurado) return ResultadoSincronizacion(0, 0, 0)
        return mutex.withLock { sincronizar(usuarioId) }
    }

    private suspend fun sincronizar(usuarioId: String?): ResultadoSincronizacion {
        var enviados = 0
        var sinConexion = 0
        var fallidos = 0
        var sinEnlazar = 0
        val parciales = 0
        val cuentasSinEnlazar = linkedSetOf<String>()
        var motivoSinConexion: String? = null
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
                    when {
                        error is SinConexionRemotaException -> {
                            alFallar(error.message ?: "Sin conexión", false)
                            motivoSinConexion = error.message
                            sinConexion++
                        }
                        // Cuenta sin token: el dato no fue rechazado. Sigue PENDING (con el motivo) hasta
                        // que esa cuenta inicie sesión con conexión; no se cuenta como error del servidor.
                        error is SinSesionServidorException -> {
                            alFallar(error.message ?: "Cuenta sin enlazar con el panel web.", false)
                            error.nombre?.let(cuentasSinEnlazar::add)
                            sinEnlazar++
                        }
                        else -> {
                            alFallar(error.message ?: "El servidor rechazó el envío.", true)
                            fallidos++
                        }
                    }
                },
            )
        }

        for (entrega in entregaRepository.pendientesDeSincronizar()) {
            if (usuarioId != null && preparador.remitente(entrega) != usuarioId) continue
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

        // El adaptador de producción envía estas marcas a la misma API oficial.
        if (remoto.configurado) for (marca in sinRecojoRepository.pendientesDeSincronizar()) {
            if (usuarioId != null && marca.usuarioId != usuarioId) continue
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

        return ResultadoSincronizacion(enviados, sinConexion, fallidos, sinEnlazar, parciales, cuentasSinEnlazar, motivoSinConexion)
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
            return servidor.enviarEntrega(preparador.remitente(entrega), datos)
        }
        if (!remoto.configurado) return Result.success(Unit)
        return remoto.publicar(entrega.aCompartido(codigo, nombre))
    }
}

/** Traduce una entrega local a lo que entiende el panel web. */
fun interface PreparadorEntregaServidor {
    suspend fun preparar(entrega: Entrega): EntregaParaServidor

    /**
     * Usuario local cuyo token firma el envío: quien hizo el último cambio. Por defecto, el acopiador que
     * la registró. El panel siempre guarda la entrega a nombre del acopiador ([EntregaParaServidor.acopiadorUsername]);
     * el remitente solo figura como autor de esa acción en su auditoría.
     */
    suspend fun remitente(entrega: Entrega): String = entrega.usuarioId
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
        val motivo = ultimoCambio(entrega)?.motivo
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
            versionServidor = entrega.versionServidor,
            revisionServidor = entrega.revisionServidor,
        )
    }

    /**
     * Si ADMIN corrigió o anuló, lo envía su propia cuenta (el panel lo audita a su nombre y la entrega
     * sigue siendo del acopiador); si no, la del acopiador. Nunca se usa el token de otra persona para
     * firmar un cambio que no hizo.
     */
    override suspend fun remitente(entrega: Entrega): String = ultimoCambio(entrega)?.usuarioId ?: entrega.usuarioId

    private suspend fun ultimoCambio(entrega: Entrega) = auditoria.filtrar(entidad = "entrega")
        .filter { it.entidadId == entrega.id && it.accion in setOf(AccionAuditoria.CORREGIR, AccionAuditoria.ANULAR, AccionAuditoria.RESOLVER_CONFLICTO) }
        .maxByOrNull { it.ocurridoEn }
}
