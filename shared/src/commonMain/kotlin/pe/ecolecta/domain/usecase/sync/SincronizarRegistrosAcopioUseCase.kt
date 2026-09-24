package pe.ecolecta.domain.usecase.sync

import kotlinx.coroutines.CancellationException
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import pe.ecolecta.domain.acopio.aCompartido
import pe.ecolecta.domain.repository.EntregaRepository
import pe.ecolecta.domain.repository.ProveedorRepository
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.domain.repository.SinConexionRemotaException
import pe.ecolecta.domain.repository.SinRecojoRepository
import pe.ecolecta.domain.repository.UsuarioRepository

data class ResultadoSincronizacion(val enviados: Int, val sinConexion: Int, val fallidos: Int) {
    val total: Int get() = enviados + sinConexion + fallidos
}

/**
 * Envía al servidor las entregas (nuevas, corregidas o anuladas) y los "sin recojo" que están
 * pendientes o con error en ESTE celular. Solo después de que el servidor acepta el documento se
 * marcan como SYNCED — y solo si no cambiaron mientras se enviaban, para no dar por sincronizada una
 * corrección que todavía no salió.
 *
 * - Sin conexión: el registro sigue PENDING (con el motivo en `sync_error`) y se reintenta después.
 * - Otro error (p. ej. dispositivo sin vincular): queda en ERROR, visible, y también se reintenta.
 * - Cada documento usa el id del registro, así que un reintento sobrescribe el mismo documento y
 *   nunca crea duplicados, aunque el primer envío sí hubiera llegado.
 * - Sin backend configurado ([RegistroAcopioRemotoRepository.configurado] = false) no hace nada: los
 *   datos quedan "guardados en este celular", que es lo que la interfaz muestra.
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
) {
    private val mutex = Mutex()

    val configurado: Boolean get() = remoto.configurado

    suspend operator fun invoke(): ResultadoSincronizacion {
        if (!remoto.configurado) return ResultadoSincronizacion(0, 0, 0)
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
                    if (error is SinConexionRemotaException) {
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
                publicar = { codigo, nombre -> remoto.publicar(entrega.aCompartido(codigo, nombre)) },
                alExito = { entregaRepository.marcarSincronizada(entrega.id, entrega.updatedAt) },
                alFallar = { mensaje, definitivo ->
                    entregaRepository.registrarFalloSync(entrega.id, entrega.updatedAt, mensaje, definitivo)
                },
            )
        }

        for (marca in sinRecojoRepository.pendientesDeSincronizar()) {
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
}
