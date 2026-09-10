package pe.ecolecta.domain.security

import kotlinx.cinterop.ExperimentalForeignApi
import kotlinx.cinterop.addressOf
import kotlinx.cinterop.usePinned
import platform.posix.arc4random_buf

@OptIn(ExperimentalForeignApi::class)
actual fun bytesAleatorios(cantidad: Int): ByteArray {
    val resultado = ByteArray(cantidad)
    if (cantidad == 0) return resultado
    resultado.usePinned { pineado ->
        arc4random_buf(pineado.addressOf(0), cantidad.toULong())
    }
    return resultado
}
