package pe.ecolecta.domain.repository

import kotlinx.serialization.json.JsonObject

/**
 * La autenticación local conserva el hash del PIN. Para crear o restablecer una cuenta en el panel,
 * la cola necesita además el PIN recuperable hasta confirmar el envío. En Android se cifra mediante
 * el protector de secretos de la plataforma; nunca se debe escribir aquí sin protección configurada.
 */
interface PinesParaServidor {
    suspend fun recordarPin(usuarioId: String, pin: String)

    /** Sin panel web (pruebas o compilación sin servidor). */
    object Ninguno : PinesParaServidor {
        override suspend fun recordarPin(usuarioId: String, pin: String) = Unit
    }
}

/** Un cambio del celular listo para enviar: [cuerpo] es el estado actual de la fila (null = ya no se envía). */
data class CambioParaServidor(
    val entidad: String,
    val localId: String,
    /** Cuenta cuyo token firma el envío (la que hizo el cambio). */
    val usuarioId: String,
    val marcadoEn: Long,
    val cuerpo: JsonObject?,
    val motivoNoEnviable: String? = null,
    val entidadRemota: String? = null,
)

/** Estado que el servidor acaba de confirmar, dentro de su transacción. */
data class ConfirmacionCatalogo(val servidorId: Long?, val revision: String? = null)

/** Resumen de la cola celular -> panel para la pantalla de sincronización. */
data class EstadoCambiosServidor(val pendientes: Long, val conError: Long, val ultimoError: String?)

/** Cola local de cambios del celular hacia el panel web (tabla cambio_pendiente). */
interface CambiosLocalesRepository {
    /** Cambios pendientes en orden de dependencia (zonas y cuentas antes que proveedores y jornadas). */
    suspend fun pendientes(): List<CambioParaServidor>

    /** El panel aceptó el cambio; [servidorId] es el id de la fila en el panel (se recuerda para no duplicar). */
    suspend fun completar(cambio: CambioParaServidor, servidorId: Long?)

    /** Guarda la revisión aceptada, incluso si existe otra edición local posterior al envío. */
    suspend fun completarCatalogo(cambio: CambioParaServidor, confirmacion: ConfirmacionCatalogo) = completar(cambio, confirmacion.servidorId)

    /** [definitivo] = el panel lo rechazó por el dato: queda en error hasta que se vuelva a cambiar. */
    suspend fun fallar(cambio: CambioParaServidor, mensaje: String, definitivo: Boolean)

    fun observarEstado(): kotlinx.coroutines.flow.Flow<EstadoCambiosServidor>
}
