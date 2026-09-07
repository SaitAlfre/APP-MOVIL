@file:OptIn(ExperimentalUnsignedTypes::class)

package pe.ecolecta.domain.security

private val K: UIntArray = uintArrayOf(
    0x428a2f98u, 0x71374491u, 0xb5c0fbcfu, 0xe9b5dba5u, 0x3956c25bu, 0x59f111f1u, 0x923f82a4u, 0xab1c5ed5u,
    0xd807aa98u, 0x12835b01u, 0x243185beu, 0x550c7dc3u, 0x72be5d74u, 0x80deb1feu, 0x9bdc06a7u, 0xc19bf174u,
    0xe49b69c1u, 0xefbe4786u, 0x0fc19dc6u, 0x240ca1ccu, 0x2de92c6fu, 0x4a7484aau, 0x5cb0a9dcu, 0x76f988dau,
    0x983e5152u, 0xa831c66du, 0xb00327c8u, 0xbf597fc7u, 0xc6e00bf3u, 0xd5a79147u, 0x06ca6351u, 0x14292967u,
    0x27b70a85u, 0x2e1b2138u, 0x4d2c6dfcu, 0x53380d13u, 0x650a7354u, 0x766a0abbu, 0x81c2c92eu, 0x92722c85u,
    0xa2bfe8a1u, 0xa81a664bu, 0xc24b8b70u, 0xc76c51a3u, 0xd192e819u, 0xd6990624u, 0xf40e3585u, 0x106aa070u,
    0x19a4c116u, 0x1e376c08u, 0x2748774cu, 0x34b0bcb5u, 0x391c0cb3u, 0x4ed8aa4au, 0x5b9cca4fu, 0x682e6ff3u,
    0x748f82eeu, 0x78a5636fu, 0x84c87814u, 0x8cc70208u, 0x90befffau, 0xa4506cebu, 0xbef9a3f7u, 0xc67178f2u,
)

private fun UInt.rotr(n: Int): UInt = (this shr n) or (this shl (32 - n))

/**
 * Implementación pura en Kotlin de SHA-256 (FIPS 180-4). No depende de APIs de plataforma,
 * lo que evita necesitar expect/actual para el hashing en sí (solo la sal aleatoria lo necesita).
 */
internal object Sha256 {
    const val TAMANO_BLOQUE = 64
    const val TAMANO_DIGEST = 32

    fun hash(mensaje: ByteArray): ByteArray {
        var h0 = 0x6a09e667u
        var h1 = 0xbb67ae85u
        var h2 = 0x3c6ef372u
        var h3 = 0xa54ff53au
        var h4 = 0x510e527fu
        var h5 = 0x9b05688cu
        var h6 = 0x1f83d9abu
        var h7 = 0x5be0cd19u

        val bitLength = mensaje.size.toULong() * 8u
        var totalLen = mensaje.size + 1
        while (totalLen % TAMANO_BLOQUE != 56) totalLen++
        val datos = ByteArray(totalLen + 8)
        mensaje.copyInto(datos)
        datos[mensaje.size] = 0x80.toByte()
        for (i in 0 until 8) {
            datos[datos.size - 1 - i] = ((bitLength shr (i * 8)) and 0xFFu).toByte()
        }

        val w = UIntArray(64)
        var offset = 0
        while (offset < datos.size) {
            for (i in 0 until 16) {
                val base = offset + i * 4
                w[i] = ((datos[base].toUInt() and 0xFFu) shl 24) or
                    ((datos[base + 1].toUInt() and 0xFFu) shl 16) or
                    ((datos[base + 2].toUInt() and 0xFFu) shl 8) or
                    (datos[base + 3].toUInt() and 0xFFu)
            }
            for (i in 16 until 64) {
                val s0 = w[i - 15].rotr(7) xor w[i - 15].rotr(18) xor (w[i - 15] shr 3)
                val s1 = w[i - 2].rotr(17) xor w[i - 2].rotr(19) xor (w[i - 2] shr 10)
                w[i] = w[i - 16] + s0 + w[i - 7] + s1
            }

            var a = h0; var b = h1; var c = h2; var d = h3
            var e = h4; var f = h5; var g = h6; var hh = h7

            for (i in 0 until 64) {
                val s1 = e.rotr(6) xor e.rotr(11) xor e.rotr(25)
                val ch = (e and f) xor (e.inv() and g)
                val temp1 = hh + s1 + ch + K[i] + w[i]
                val s0 = a.rotr(2) xor a.rotr(13) xor a.rotr(22)
                val maj = (a and b) xor (a and c) xor (b and c)
                val temp2 = s0 + maj

                hh = g; g = f; f = e; e = d + temp1
                d = c; c = b; b = a; a = temp1 + temp2
            }

            h0 += a; h1 += b; h2 += c; h3 += d
            h4 += e; h5 += f; h6 += g; h7 += hh

            offset += TAMANO_BLOQUE
        }

        val resultado = ByteArray(TAMANO_DIGEST)
        val palabras = uintArrayOf(h0, h1, h2, h3, h4, h5, h6, h7)
        for (i in palabras.indices) {
            resultado[i * 4] = (palabras[i] shr 24).toByte()
            resultado[i * 4 + 1] = (palabras[i] shr 16).toByte()
            resultado[i * 4 + 2] = (palabras[i] shr 8).toByte()
            resultado[i * 4 + 3] = palabras[i].toByte()
        }
        return resultado
    }
}

internal object HmacSha256 {
    fun hmac(clave: ByteArray, mensaje: ByteArray): ByteArray {
        val bloque = Sha256.TAMANO_BLOQUE
        val keyBase = if (clave.size > bloque) Sha256.hash(clave) else clave
        val key = ByteArray(bloque)
        keyBase.copyInto(key)

        val ipad = ByteArray(bloque) { (key[it].toInt() xor 0x36).toByte() }
        val opad = ByteArray(bloque) { (key[it].toInt() xor 0x5c).toByte() }

        val innerHash = Sha256.hash(ipad + mensaje)
        return Sha256.hash(opad + innerHash)
    }
}

internal object Pbkdf2 {
    const val ITERACIONES_DEFECTO = 20_000

    fun derivar(
        pin: String,
        salt: ByteArray,
        iteraciones: Int = ITERACIONES_DEFECTO,
        longitudClave: Int = 32,
    ): ByteArray {
        val password = pin.encodeToByteArray()
        val hLen = Sha256.TAMANO_DIGEST
        val numBloques = (longitudClave + hLen - 1) / hLen
        val resultado = ByteArray(numBloques * hLen)

        for (bloque in 1..numBloques) {
            val indiceBloque = byteArrayOf(
                (bloque shr 24).toByte(),
                (bloque shr 16).toByte(),
                (bloque shr 8).toByte(),
                bloque.toByte(),
            )

            var u = HmacSha256.hmac(password, salt + indiceBloque)
            val t = u.copyOf()
            for (iter in 2..iteraciones) {
                u = HmacSha256.hmac(password, u)
                for (i in t.indices) t[i] = (t[i].toInt() xor u[i].toInt()).toByte()
            }
            t.copyInto(resultado, (bloque - 1) * hLen)
        }
        return resultado.copyOf(longitudClave)
    }
}

internal fun ByteArray.aHex(): String = joinToString("") { (it.toInt() and 0xFF).toString(16).padStart(2, '0') }

internal fun String.deHex(): ByteArray {
    require(length % 2 == 0) { "Cadena hexadecimal inválida" }
    return ByteArray(length / 2) { i -> ((this[i * 2].digitToInt(16) shl 4) or this[i * 2 + 1].digitToInt(16)).toByte() }
}
