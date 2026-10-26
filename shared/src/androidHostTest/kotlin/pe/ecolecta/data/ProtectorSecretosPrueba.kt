package pe.ecolecta.data

import pe.ecolecta.domain.security.ProtectorSecretos
import pe.ecolecta.domain.security.SecretoLocalNoDisponibleException
import java.util.Base64
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.spec.GCMParameterSpec

/** AES real para fixtures JVM; no representa Android Keystore ni se incluye en la app. */
object ProtectorSecretosPrueba : ProtectorSecretos {
    private val clave = KeyGenerator.getInstance("AES").apply { init(256) }.generateKey()
    override fun proteger(valor: String, contexto: String): String {
        val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply { init(Cipher.ENCRYPT_MODE, clave); updateAAD(contexto.toByteArray()) }
        return "ks1:" + Base64.getEncoder().encodeToString(cipher.iv) + ":" + Base64.getEncoder().encodeToString(cipher.doFinal(valor.toByteArray()))
    }
    override fun abrir(valor: String, contexto: String): String {
        if (!valor.startsWith("ks1:")) return valor
        return try {
            val partes = valor.removePrefix("ks1:").split(':')
            val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply {
                init(Cipher.DECRYPT_MODE, clave, GCMParameterSpec(128, Base64.getDecoder().decode(partes[0])))
                updateAAD(contexto.toByteArray())
            }
            String(cipher.doFinal(Base64.getDecoder().decode(partes[1])))
        } catch (_: Exception) { throw SecretoLocalNoDisponibleException() }
    }
}
