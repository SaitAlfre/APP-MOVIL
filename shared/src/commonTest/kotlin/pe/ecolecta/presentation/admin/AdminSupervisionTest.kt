package pe.ecolecta.presentation.admin

import kotlin.test.*
import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.*
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.usecase.proveedor.*
import pe.ecolecta.presentation.admin.supervision.*
import pe.ecolecta.presentation.design.formatearFecha

class AdminSupervisionTest {
    private fun control(id: String = "c1") = ControlCalidad(
        id, "p1", "u1", "MUE-001", null, null, OrigenCaptura.MANUAL, "49731", "1",
        6.0, 3.5, 8.7, 1.03, 3.2, 4.7, 0.7, 12.2, 99.9, -0.53, 6.7,
        null, null, EstadoControlCalidad.RECHAZADO, listOf("Agua añadida"), null, 1_789_800_000_000, 0, SyncState.PENDING,
        DatosVisitaCalidad(proveedorNombre = "El Rosal", tecnicoNombre = "Miguel Vargas", zonaId = "z1"),
    )

    @Test fun filtrosSeCombinanYExcluyenEjemplos() {
        val c = control()
        val datos = AdminSupervisionState(controles = listOf(c, c.copy(id = "demo", visita = c.visita.copy(ejemplo = true))))
        assertEquals(listOf(c), filtrarControlesAdmin(datos, "p1", "z1", "RECHAZADO", "miguel", formatearFecha(c.registradoEn)))
        assertTrue(filtrarControlesAdmin(datos, "p2", null, null, "", "").isEmpty())
        assertTrue(filtrarControlesAdmin(datos, null, "z2", null, "", "").isEmpty())
        assertTrue(filtrarControlesAdmin(datos, null, null, "APROBADO", "", "").isEmpty())
        assertTrue(filtrarControlesAdmin(datos, null, null, null, "", "01/01/2000").isEmpty())
    }

    @Test fun usaZonaHistoricaAunqueProveedorCambieDeZona() {
        val c = control()
        val p = Proveedor("p1", "PRV-1", "Nombre actual", "123", null, null, "z2", 1, 40.0, EstadoProveedor.ACTIVO, 0, SyncState.SYNCED)
        val datos = AdminSupervisionState(controles = listOf(c), proveedores = listOf(p))
        assertEquals("z1", datos.zonaId(c))
        assertEquals("El Rosal", datos.nombre(c))
        val antiguo = c.copy(visita = DatosVisitaCalidad())
        assertEquals("z2", datos.zonaId(antiguo))
        assertEquals("Nombre actual", datos.nombre(antiguo))
    }

    @Test fun crearYEditarResponsableConservaCuentaVinculada() = runTest {
        val repo = FakeProveedorRepository()
        val p = CrearProveedorUseCase(repo, FakeReloj())("P1", "El Rosal", "123", null, null, "z1", dueno = " Rosa ").getOrThrow()
        assertEquals("Rosa", repo.obtenerPorId(p.id)?.dueno)
        repo.vincularUsuario(p.id, "usuario-1")
        val editar = ActualizarProveedorUseCase(repo, FakeReloj())
        editar(p.id, p.codigo, p.nombres, p.dni, null, null, "z1", 1, 40.0, EstadoProveedor.ACTIVO, "Ana").getOrThrow()
        assertEquals("Ana", repo.obtenerPorId(p.id)?.dueno)
        assertEquals("usuario-1", repo.obtenerPorId(p.id)?.usuarioId)
        editar(p.id, p.codigo, p.nombres, p.dni, null, null, "z1", 1, 40.0, EstadoProveedor.ACTIVO).getOrThrow()
        assertEquals("Ana", repo.obtenerPorId(p.id)?.dueno)
    }
}
