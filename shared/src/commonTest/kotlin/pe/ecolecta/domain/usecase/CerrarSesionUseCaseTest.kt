package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.remote.ServidorWebNoConfigurado
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.ServidorWebRepository
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull

class CerrarSesionUseCaseTest {
    private val sesiones = InMemorySesionRepository()
    private val usuario = Usuario("prueba", "admin", "Administrador de prueba", "00000001", "hash", "salt", true, listOf(Rol.ADMIN), 1)

    @Test
    fun cierraLaSesionLocalAntesDeSolicitarRevocacionRemota() = runTest {
        sesiones.iniciar(Sesion(usuario, Rol.ADMIN))
        var revocado: String? = null
        val servidor = object : ServidorWebRepository by ServidorWebNoConfigurado() {
            override suspend fun desvincular(usuarioId: String) {
                assertNull(sesiones.observar().first())
                revocado = usuarioId
            }
        }
        CerrarSesionUseCase(sesiones, servidor)()
        assertEquals(usuario.id, revocado)
    }

    @Test
    fun unErrorDeRedNoConservaElAccesoLocal() = runTest {
        sesiones.iniciar(Sesion(usuario, Rol.ADMIN))
        val servidor = object : ServidorWebRepository by ServidorWebNoConfigurado() {
            override suspend fun desvincular(usuarioId: String) { error("Servidor de pruebas sin conexión") }
        }
        CerrarSesionUseCase(sesiones, servidor)()
        assertNull(sesiones.observar().first())
    }

    @Test
    fun sinSesionNoSolicitaRevocacionDeOtraCuenta() = runTest {
        var llamadas = 0
        val servidor = object : ServidorWebRepository by ServidorWebNoConfigurado() {
            override suspend fun desvincular(usuarioId: String) { llamadas++ }
        }
        CerrarSesionUseCase(sesiones, servidor)()
        assertEquals(0, llamadas)
    }
}
