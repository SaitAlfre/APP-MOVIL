package pe.ecolecta.domain.security

import kotlinx.cinterop.ByteVar
import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.allocArray
import kotlinx.cinterop.memScoped
import platform.posix.arc4random_buf

@OptIn(ExperimentalForeignApi::class)
actual fun bytesAleatorios(cantidad: Int): ByteArray {
    val resultado = ByteArray(cantidad)
    if (cantidad == 0) return resultado
    memScoped {
        val buffer = allocArray<ByteVar>(cantidad)
        arc4random_buf(buffer, cantidad.toULong())
        for (i in 0 until cantidad) resultado[i] = buffer[i]
    }
    return resultado
}
