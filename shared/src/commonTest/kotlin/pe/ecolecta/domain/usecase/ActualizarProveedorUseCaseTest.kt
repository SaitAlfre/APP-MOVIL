package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.ProveedorInvalidoException
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.proveedor.ActualizarProveedorUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

class ActualizarProveedorUseCaseTest {
    private val repo = FakeProveedorRepository()
    private val actualizar = ActualizarProveedorUseCase(repo, FakeReloj())

    private suspend fun sembrar(id: String, codigo: String, dni: String) = repo.insertar(
        Proveedor.crear(id = id, codigo = codigo, nombres = "Ficha $codigo", dni = dni, telefono = null, direccion = null,
            zonaId = "z1", updatedAt = 0L).getOrThrow(),
    )

    private suspend fun editar(id: String, codigo: String, dni: String, telefono: String?) =
        actualizar(id, codigo, "Ficha $codigo", dni, telefono, null, "z1", 2, 40.0, EstadoProveedor.ACTIVO)

    @Test
    fun `una ficha con documento repetido de datos antiguos se puede editar sin cambiarlo`() = runTest {
        sembrar("elena", "PRV-002", "10000002")
        sembrar("faon02", "PRV-FAON-02", "10000002")

        assertTrue(editar("elena", "PRV-002", "10000002", "987654321").isSuccess)
        assertEquals("987654321", repo.obtenerPorId("elena")?.telefono)
    }

    @Test
    fun `cambiar a un documento de otra ficha se rechaza`() = runTest {
        sembrar("elena", "PRV-002", "10000002")
        sembrar("rosa", "PRV-003", "10000003")

        assertIs<ProveedorInvalidoException.DniDuplicado>(editar("elena", "PRV-002", "10000003", null).exceptionOrNull())
        assertEquals("10000002", repo.obtenerPorId("elena")?.dni)
    }
}
