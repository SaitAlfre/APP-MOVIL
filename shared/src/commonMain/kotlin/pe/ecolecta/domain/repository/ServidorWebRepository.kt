package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import kotlinx.serialization.Serializable
import pe.ecolecta.domain.model.PagoProveedor

/**
 * Entrega tal como la recibe el panel web. Los ids locales (proveedor, zona, vehículo, usuario) no
 * existen en el servidor, así que viajan su código, nombre, placa y usuario; el id de la entrega y de
 * la jornada sí viajan: son la clave idempotente (reenviar actualiza la misma fila, nunca duplica).
 */
@Serializable
data class EntregaParaServidor(
    val id: String,
    val jornadaId: String,
    val jornadaAbiertaEn: Long,
    val jornadaCerradaEn: Long?,
    val proveedorCodigo: String,
    val zonaNombre: String,
    val vehiculoPlaca: String,
    val acopiadorUsername: String,
    val registradoEn: Long,
    val litros: Double,
    val tachos: Int,
    val observaciones: String?,
    val anulada: Boolean,
    /** Motivo de la última corrección o anulación, para la auditoría del panel. */
    val motivo: String?,
    /** Reloj lógico (updated_at local): el servidor no aplica una versión más vieja que la guardada. */
    val actualizadoEn: Long,
)

/** El servidor respondió y no aceptó el dato; [mensaje] es el motivo que se muestra en el celular. */
class RechazoServidorException(mensaje: String, val codigo: String?) : Exception(mensaje)

/** Este usuario no tiene sesión con el servidor en este celular (nunca la tuvo, venció o fue revocada). */
class SinSesionServidorException(nombre: String?) : Exception(
    "${nombre ?: "El acopiador"} debe iniciar sesión en este celular con conexión a internet para enlazarse con el servidor.",
)

/**
 * Panel web (Laravel): fuente oficial de entregas (lo que ve el administrador) y de liquidaciones
 * generadas (pendientes de pago o pagadas). Cada usuario obtiene su PROPIO token con su usuario y PIN; el APK no lleva credenciales.
 */
interface ServidorWebRepository {
    /** false si esta compilación no tiene URL de servidor: la app lo muestra, no finge sincronizar. */
    val configurado: Boolean

    /** Valida usuario/PIN en el servidor y guarda el token del usuario local [usuarioId]. */
    suspend fun vincular(usuarioId: String, username: String, pin: String): Result<Unit>

    /** true mientras [usuarioId] tenga un token vigente en este celular. */
    fun observarSesion(usuarioId: String): Flow<Boolean>

    /** Envía con el token del autor ([usuarioId]). Éxito solo cuando el servidor confirmó la escritura. */
    suspend fun enviarEntrega(usuarioId: String, entrega: EntregaParaServidor): Result<Unit>

    /** Liquidaciones generadas en el panel (estado real: PENDIENTE o PAGADA) del proveedor vinculado a la cuenta [usuarioId] (con `proveedorId` vacío). */
    suspend fun liquidacionesDelProveedor(usuarioId: String): Result<List<PagoProveedor>>

    /** Revoca el token en el servidor (si hay conexión) y lo borra de este celular. */
    suspend fun desvincular(usuarioId: String)
}
