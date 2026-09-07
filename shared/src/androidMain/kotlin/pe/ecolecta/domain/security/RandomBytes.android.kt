package pe.ecolecta.domain.security

import java.security.SecureRandom

actual fun bytesAleatorios(cantidad: Int): ByteArray {
    val bytes = ByteArray(cantidad)
    SecureRandom().nextBytes(bytes)
    return bytes
}
