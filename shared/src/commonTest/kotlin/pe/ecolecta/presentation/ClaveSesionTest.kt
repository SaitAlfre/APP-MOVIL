package pe.ecolecta.presentation

import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotEquals

/**
 * `claveSesionDe` es la clave de la que depende [rememberSesionViewModelStoreOwner] en App.kt:
 * Compose solo recrea el ViewModelStoreOwner (y limpia el anterior) cuando esta clave cambia
 * respecto a la INMEDIATA ANTERIOR. Lo que importa entonces no es el valor aislado, sino que dos
 * transiciones consecutivas nunca coincidan — ni siquiera al volver a entrar como el mismo usuario.
 *
 * Esto prueba la lógica de la clave, no una recomposición real de Compose (este repo no tiene
 * infraestructura de test de Compose): la verificación de que los ViewModels viejos realmente se
 * descartan se hizo a mano en el emulador, sin forzar el cierre de la app.
 */
class ClaveSesionTest {
    private fun usuario(id: String, username: String): Usuario {
        val hash = Pbkdf2PinHasher().crearHash("1234")
        return Usuario.crear(
            id = id, username = username, nombres = username, dni = id.take(8).padStart(8, '0'),
            pinHash = hash.hash, pinSalt = hash.salt, activo = true, roles = listOf(Rol.ACOPIADOR), updatedAt = 0L,
        ).getOrThrow()
    }

    @Test
    fun `sin sesion produce la clave reservada`() {
        assertEquals("sin-sesion", claveSesionDe(null))
    }

    @Test
    fun `una sesion produce el id del usuario`() {
        val sesion = Sesion(usuario("u1", "jperez"), Rol.ACOPIADOR)
        assertEquals("u1", claveSesionDe(sesion))
    }

    @Test
    fun `logout y login del mismo usuario nunca repiten la clave consecutiva`() {
        val sesion = Sesion(usuario("u1", "jperez"), Rol.ACOPIADOR)
        val secuencia = listOf(claveSesionDe(sesion), claveSesionDe(null), claveSesionDe(sesion))
        for (i in 0 until secuencia.size - 1) {
            assertNotEquals(secuencia[i], secuencia[i + 1], "las claves consecutivas $i y ${i + 1} no deben coincidir")
        }
    }

    @Test
    fun `cambiar a un usuario distinto sin pasar por logout tambien cambia la clave`() {
        val sesionA = Sesion(usuario("u1", "jperez"), Rol.ACOPIADOR)
        val sesionB = Sesion(usuario("u2", "acopiador_prueba"), Rol.ACOPIADOR)
        assertNotEquals(claveSesionDe(sesionA), claveSesionDe(sesionB))
    }
}
