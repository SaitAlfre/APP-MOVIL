package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.generarQrProveedor
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.usecase.proveedor.EscanearQrProveedorUseCase
import pe.ecolecta.domain.usecase.proveedor.ResultadoEscaneoQr
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs

class EscanearQrProveedorUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val useCase = EscanearQrProveedorUseCase(proveedorRepository)

    private suspend fun insertarProveedor(id: String, estado: EstadoProveedor = EstadoProveedor.ACTIVO): Proveedor {
        val proveedor = Proveedor.crear(
            id = id, codigo = "PRV-00$id", nombres = "Proveedor $id", dni = "1000000$id",
            telefono = null, direccion = null, zonaId = "z1", estado = estado, updatedAt = 0L,
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)
        return proveedor
    }

    @Test
    fun `resuelve el proveedor cuando el QR es valido y existe`() = runTest {
        val proveedor = insertarProveedor("1")

        val resultado = useCase(generarQrProveedor(proveedor.id))

        assertIs<ResultadoEscaneoQr.Encontrado>(resultado)
        assertEquals(proveedor.id, resultado.proveedor.id)
    }

    @Test
    fun `devuelve QrInvalido si el contenido no tiene el prefijo esperado`() = runTest {
        val resultado = useCase("cualquier-texto-que-no-es-un-qr-de-ecolecta")

        assertIs<ResultadoEscaneoQr.QrInvalido>(resultado)
    }

    @Test
    fun `devuelve ProveedorNoEncontrado si el id no existe en el repositorio local`() = runTest {
        val resultado = useCase(generarQrProveedor("id-inexistente"))

        assertIs<ResultadoEscaneoQr.ProveedorNoEncontrado>(resultado)
    }

    @Test
    fun `resuelve el proveedor sin importar su estado para poder mostrarlo y bloquear despues`() = runTest {
        val proveedor = insertarProveedor("2", estado = EstadoProveedor.SUSPENDIDO)

        val resultado = useCase(generarQrProveedor(proveedor.id))

        assertIs<ResultadoEscaneoQr.Encontrado>(resultado)
        assertEquals(EstadoProveedor.SUSPENDIDO, resultado.proveedor.estado)
    }
}
