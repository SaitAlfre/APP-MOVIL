package pe.ecolecta.presentation

import kotlinx.coroutines.CancellationException

/**
 * Ejecuta [bloque] devolviendo su resultado envuelto en [Result].
 *
 * A diferencia de un `try/catch` directo sobre `Exception`, deja que una [CancellationException]
 * siga propagándose: convertirla en un `Result.failure` rompería la cancelación estructurada del
 * `CoroutineScope` que envuelve la llamada (p. ej. el `viewModelScope` se cancela en `onCleared()`
 * lanzando esta excepción dentro de cada corrutina hija — si la atrapáramos como un error de negocio,
 * la corrutina seguiría corriendo después de que el ViewModel debería estar destruido).
 */
suspend inline fun <T> cargaSegura(bloque: suspend () -> T): Result<T> = try {
    Result.success(bloque())
} catch (e: CancellationException) {
    throw e
} catch (e: Exception) {
    Result.failure(e)
}
