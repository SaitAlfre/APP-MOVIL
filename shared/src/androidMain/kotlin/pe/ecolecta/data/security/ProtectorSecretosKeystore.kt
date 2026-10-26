package pe.ecolecta.data.security

import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import pe.ecolecta.domain.security.ProtectorSecretos
import pe.ecolecta.domain.security.SecretoLocalNoDisponibleException
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/** Clave AES no exportable del Android Keystore. No requiere dependencias ni guarda la clave en SQLite. */
class ProtectorSecretosKeystore : ProtectorSecretos {
    companion object {
        private const val PREFIJO = "ks1:"
        private const val ALIAS = "ecolecta.credenciales.v1"
        private val candado = Any()
    }

    private fun clave(): SecretKey = synchronized(candado) {
        val almacen = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        (almacen.getKey(ALIAS, null) as? SecretKey) ?: KeyGenerator.getInstance("AES", "AndroidKeyStore").run {
            init(KeyGenParameterSpec.Builder(ALIAS, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM).setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setKeySize(256).setRandomizedEncryptionRequired(true).build())
            generateKey()
        }
    }

    override fun proteger(valor: String, contexto: String): String = try {
        val cifrador = Cipher.getInstance("AES/GCM/NoPadding")
        cifrador.init(Cipher.ENCRYPT_MODE, clave())
        cifrador.updateAAD(contexto.toByteArray(Charsets.UTF_8))
        val cifrado = cifrador.doFinal(valor.toByteArray(Charsets.UTF_8))
        PREFIJO + Base64.encodeToString(cifrador.iv, Base64.NO_WRAP) + ":" + Base64.encodeToString(cifrado, Base64.NO_WRAP)
    } catch (_: Exception) { throw SecretoLocalNoDisponibleException() }

    override fun abrir(valor: String, contexto: String): String {
        // Compatibilidad de lectura durante la conversión local de credenciales anteriores.
        if (!valor.startsWith(PREFIJO)) return valor
        return try {
            val partes = valor.removePrefix(PREFIJO).split(':')
            require(partes.size == 2)
            val cifrador = Cipher.getInstance("AES/GCM/NoPadding")
            cifrador.init(Cipher.DECRYPT_MODE, clave(), GCMParameterSpec(128, Base64.decode(partes[0], Base64.NO_WRAP)))
            cifrador.updateAAD(contexto.toByteArray(Charsets.UTF_8))
            String(cifrador.doFinal(Base64.decode(partes[1], Base64.NO_WRAP)), Charsets.UTF_8)
        } catch (_: Exception) { throw SecretoLocalNoDisponibleException() }
    }
}
