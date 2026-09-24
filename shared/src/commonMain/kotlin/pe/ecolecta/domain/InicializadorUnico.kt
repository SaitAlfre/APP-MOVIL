package pe.ecolecta.domain

import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

/**
 * Coordina la inicialización perezosa de un recurso compartido (p. ej. una sesión anónima remota)
 * entre corrutinas concurrentes que no tienen ninguna coordinación previa entre sí — como el job de
 * sincronización periódica de registros, la escucha del portal del proveedor, o la pantalla de Perfil.
 *
 * Sin este candado, dos llamadas concurrentes pueden ver el recurso como "todavía no existe" a la
 * vez y disparar [inicializar] por duplicado. Para una sesión anónima de Firebase eso es
 * especialmente grave: sin sesión previa, cada llamada a `signInAnonymously()` crea un usuario
 * nuevo en el servidor — nunca reutiliza uno existente — dejando uids huérfanos sin vínculo
 * administrado y condenando esa escritura a PERMISSION_DENIED.
 *
 * Verificación rápida sin candado para el caso común (ya inicializado); solo adquiere el mutex, y
 * vuelve a verificar dentro de él, cuando de verdad hace falta — así una corrutina que estuvo
 * esperando el candado reutiliza lo que la que llegó primero ya inicializó, en vez de inicializar
 * una segunda vez.
 */
class InicializadorUnico<T>(
    private val obtenerExistente: suspend () -> T?,
    private val inicializar: suspend () -> T?,
) {
    private val mutex = Mutex()

    suspend fun obtener(): T? {
        obtenerExistente()?.let { return it }
        return mutex.withLock {
            obtenerExistente() ?: inicializar()
        }
    }
}
