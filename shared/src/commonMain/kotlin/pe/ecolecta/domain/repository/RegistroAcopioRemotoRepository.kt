package pe.ecolecta.domain.repository

import kotlinx.coroutines.flow.Flow
import pe.ecolecta.domain.acopio.RegistroAcopioCompartido

/** Resultado de escuchar los registros compartidos de UN proveedor. */
sealed interface EventoRegistrosRemotos {
    /** [desdeCache] = true: el servidor no respondió y esto es la última copia conocida. */
    data class Recibidos(val registros: List<RegistroAcopioCompartido>, val desdeCache: Boolean = false) : EventoRegistrosRemotos

    /** Sin internet en este dispositivo: se sigue mostrando lo último recibido. */
    data object SinConexion : EventoRegistrosRemotos

    /** El servidor rechazó la consulta o no respondió por otro motivo (p. ej. cuenta sin vincular). */
    data class NoDisponible(val motivo: String) : EventoRegistrosRemotos
}

/** El envío falló por falta de conexión: se conserva el dato y se reintenta sin marcarlo como error. */
class SinConexionRemotaException(causa: Throwable? = null) : Exception("Sin conexión a internet", causa)

/**
 * Canal que comparte entregas y "sin recojo" entre el celular del acopiador y el del proveedor
 * (colección `registros_acopio` de Firestore en Android). Cada documento se identifica con el id del
 * registro local, así que reenviar el mismo registro lo sobrescribe y nunca crea duplicados.
 */
interface RegistroAcopioRemotoRepository {
    /** false si esta compilación/plataforma no tiene backend remoto (iOS, vista local de Android). */
    val configurado: Boolean

    suspend fun publicar(registro: RegistroAcopioCompartido): Result<Unit>

    /** Solo los registros del propio proveedor: las reglas del servidor rechazan cualquier otro código. */
    fun observarDeProveedor(proveedorCodigo: String): Flow<EventoRegistrosRemotos>
}

/** Última copia recibida en el celular del proveedor, para mostrarla sin conexión. */
interface RegistroRecibidoRepository {
    fun observarPorCodigo(proveedorCodigo: String): Flow<List<RegistroAcopioCompartido>>
    suspend fun guardar(registros: List<RegistroAcopioCompartido>, recibidoEn: Long)
    suspend fun ultimaRecepcion(proveedorCodigo: String): Long?
}
