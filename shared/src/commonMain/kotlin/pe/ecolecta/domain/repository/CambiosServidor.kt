package pe.ecolecta.domain.repository

import kotlinx.serialization.json.JsonObject

/**
 * El PIN solo se guarda en el celular como hash, pero el panel web necesita el PIN en claro para crear o
 * restablecer la cuenta. Quien fija un PIN lo entrega aquí; se conserva solo hasta enviarlo al panel.
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
)

/** Resumen de la cola celular -> panel para la pantalla de sincronización. */
data class EstadoCambiosServidor(val pendientes: Long, val conError: Long, val ultimoError: String?)

/** Cola local de cambios del celular hacia el panel web (tabla cambio_pendiente). */
interface CambiosLocalesRepository {
    /** Cambios pendientes en orden de dependencia (zonas y cuentas antes que proveedores y jornadas). */
    suspend fun pendientes(): List<CambioParaServidor>

    /** El panel aceptó el cambio; [servidorId] es el id de la fila en el panel (se recuerda para no duplicar). */
    suspend fun completar(cambio: CambioParaServidor, servidorId: Long?)

    /** [definitivo] = el panel lo rechazó por el dato: queda en error hasta que se vuelva a cambiar. */
    suspend fun fallar(cambio: CambioParaServidor, mensaje: String, definitivo: Boolean)

    fun observarEstado(): kotlinx.coroutines.flow.Flow<EstadoCambiosServidor>
}
