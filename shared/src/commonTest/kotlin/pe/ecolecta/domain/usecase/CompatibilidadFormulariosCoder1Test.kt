package pe.ecolecta.domain.usecase

import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.usecase.proveedor.ActualizarProveedorUseCase
import pe.ecolecta.presentation.admin.usuarios.UsuarioFormUiState
import kotlin.test.*

class CompatibilidadFormulariosCoder1Test {
    private fun ficha(codigo: String = "QA", nombres: String = "Finca", dni: String = "12345678", telefono: String? = null,
        direccion: String? = null, capacidad: Double = 40.0) = Proveedor.crear("p", codigo, nombres, dni, telefono, direccion, "z", 2, capacidad, updatedAt = 0)

    @Test fun zonaYVehiculoRespetanLosLimitesDelPanel() {
        assertTrue(Zona.crear("z", "Z".repeat(100), true).isSuccess)
        assertTrue(Zona.crear("z", "Z".repeat(101), true).isFailure)
        assertTrue(Vehiculo.crear("v", "V".repeat(100), "P".repeat(20), true).isSuccess)
        assertTrue(Vehiculo.crear("v", "V".repeat(101), "P", true).isFailure)
        assertTrue(Vehiculo.crear("v", "V", "P".repeat(21), true).isFailure)
    }

    @Test fun camposProveedorNoSeTruncanYValidanElContratoLaravel() {
        val p = ficha("C".repeat(50), "N".repeat(150), "D".repeat(20), "T".repeat(30), "A".repeat(255)).getOrThrow()
        assertEquals(30, p.telefono?.length)
        assertTrue(ficha(codigo = "C".repeat(51)).isFailure)
        assertTrue(ficha(nombres = "N".repeat(151)).isFailure)
        assertTrue(ficha(dni = "D".repeat(21)).isFailure)
        assertTrue(ficha(telefono = "T".repeat(31)).isFailure)
        assertTrue(ficha(direccion = "A".repeat(256)).isFailure)
    }

    @Test fun capacidadFinitaYTransportable() {
        for (valor in listOf(Double.NaN, Double.POSITIVE_INFINITY, Double.NEGATIVE_INFINITY, 0.009, 0.0)) assertTrue(ficha(capacidad = valor).isFailure)
        assertEquals(0.01, ficha(capacidad = 0.01).getOrThrow().capacidadTachoL)
    }

    @Test fun editarNoRobaUnCodigoExistente() = runTest {
        val repo = FakeProveedorRepository()
        repo.insertar(ficha(codigo = "QA-1").getOrThrow())
        repo.insertar(ficha(codigo = "QA-2", dni = "87654321").getOrThrow().copy(id = "otra"))
        val editar = ActualizarProveedorUseCase(repo, FakeReloj())
        assertTrue(editar("p", "QA-2", "Finca", "12345678", null, null, "z", 2, 40.0, EstadoProveedor.ACTIVO).isFailure)
        assertEquals("QA-1", repo.obtenerPorId("p")?.codigo)
        assertTrue(editar("ausente", "QA-3", "Finca", "12345678", null, null, "z", 2, 40.0, EstadoProveedor.ACTIVO).isFailure)
    }

    @Test fun documentoLegadoNoImpideEditarOtrosDatosEnLaPantalla() {
        val estado = UsuarioFormUiState(esEdicion = true, nombres = "Cuenta ficticia", dni = "LEGADO-123456", dniOriginal = "LEGADO-123456", roles = setOf(Rol.ACOPIADOR))
        assertNull(estado.faltante)
        assertNotNull(estado.copy(dni = "OTRO-LEGADO").faltante)
    }
    @Test fun calidadLegadaSinZonaNoObligaReasignarParaEditarNombre() {
        val legado = UsuarioFormUiState(esEdicion = true, nombres = "Nombre ficticio", dni = "12345678", dniOriginal = "12345678",
            username = "qa_legado", roles = setOf(Rol.CALIDAD), rolesOriginales = setOf(Rol.CALIDAD))
        assertNull(legado.faltante)
        assertNotNull(legado.copy(esEdicion = false).faltante)
        assertNotNull(legado.copy(rolesOriginales = setOf(Rol.ACOPIADOR)).faltante)
    }

    @Test fun identidadNuevaNoAdmiteDniVacioNiUsuarioFueraDelContrato() {
        val nuevo = UsuarioFormUiState(nombres = "Nombre ficticio", dni = "12345678", username = "qa_c1", roles = setOf(Rol.ACOPIADOR), pin = "1357", confirmacionPin = "1357")
        assertNull(nuevo.faltante)
        assertNotNull(nuevo.copy(dni = "").faltante)
        for (usuario in listOf("a", "qa-c1", "x".repeat(31), "qa\n")) assertNotNull(nuevo.copy(username = usuario).faltante)
        assertNull(nuevo.copy(username = "qa.c1_26").faltante)
    }

    @Test fun formularioConjuntoUsaElMismoContratoDeFichaQueElCatalogo() {
        val estado = UsuarioFormUiState(nombres = "Titular ficticio", dni = "12345678", username = "qa_finca", roles = setOf(Rol.PROVEEDOR),
            fichaCodigo = "C".repeat(50), fichaNombre = "N".repeat(150), fichaDocumento = "D".repeat(20), fichaZonaId = "z",
            fichaTelefono = "T".repeat(30), fichaDireccion = "A".repeat(255), fichaTachos = "1000", fichaCapacidad = "200.01", pin = "1357", confirmacionPin = "1357")
        assertNull(estado.faltante)
        assertNotNull(estado.copy(fichaCodigo = "C".repeat(51)).faltante)
        assertNotNull(estado.copy(fichaTachos = "1001").faltante)
        assertNotNull(estado.copy(fichaCapacidad = "NaN").faltante)
        assertNotNull(estado.copy(fichaCapacidad = "0.009").faltante)
        assertNull(estado.copy(fichaCapacidad = "0.01").faltante)
    }

}
