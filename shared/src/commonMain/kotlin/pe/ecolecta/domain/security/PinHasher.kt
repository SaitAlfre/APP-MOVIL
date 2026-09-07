package pe.ecolecta.domain.security

/** Genera bytes aleatorios criptográficamente seguros para la sal del PIN (único punto expect/actual del hashing). */
expect fun bytesAleatorios(cantidad: Int): ByteArray

data class ParHashPin(val hash: String, val salt: String)

interface PinHasher {
    fun crearHash(pin: String): ParHashPin
    fun verificar(pin: String, saltHex: String, hashEsperado: String): Boolean
}

/** PBKDF2-HMAC-SHA256 puro en Kotlin, por eso el PIN nunca se guarda ni compara en texto plano (§4). */
class Pbkdf2PinHasher : PinHasher {
    override fun crearHash(pin: String): ParHashPin {
        val salt = bytesAleatorios(16)
        val hash = Pbkdf2.derivar(pin, salt)
        return ParHashPin(hash = hash.aHex(), salt = salt.aHex())
    }

    override fun verificar(pin: String, saltHex: String, hashEsperado: String): Boolean {
        val calculado = Pbkdf2.derivar(pin, saltHex.deHex()).aHex()
        return compararEnTiempoConstante(calculado, hashEsperado)
    }

    private fun compararEnTiempoConstante(a: String, b: String): Boolean {
        if (a.length != b.length) return false
        var diferencia = 0
        for (i in a.indices) diferencia = diferencia or (a[i].code xor b[i].code)
        return diferencia == 0
    }
}
