package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.usecase.usuario.ReglasCuenta
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFailsWith

class ReglasCuentaTest {
    private val usuarios = FakeUsuarioRepository()
    private val proveedores = FakeProveedorRepository()
    private val jornadas = FakeJornadaRepository()
    private val zonas = object : ZonaRepository {
        val lista = listOf(Zona("z1", "FAON", true), Zona("z2", "CERRADA", false))
        override fun observarTodas(): Flow<List<Zona>> = flowOf(lista)
        override fun observarActivas(): Flow<List<Zona>> = flowOf(lista.filter { it.activo })
        override suspend fun obtenerPorId(id: String) = lista.firstOrNull { it.id == id }
        override suspend fun insertar(zona: Zona) = Unit
        override suspend fun actualizar(zona: Zona) = Unit
        override suspend fun desactivar(id: String) = Unit
        override suspend fun contarProveedoresEnZona(id: String) = 0L
    }
    private val reglas = ReglasCuenta(usuarios, zonas, proveedores, jornadas)

    private fun usuario(id: String, vararg roles: Rol, activo: Boolean = true) =
        Usuario(id, id, "Nombre $id", "1234567$id".takeLast(8), "h", "s", activo, roles.toList(), 0)

    @Test
    fun `nadie puede desactivarse a si mismo`() = runTest {
        val admin = usuario("a", Rol.ADMIN)
        usuarios.insertar(admin)
        usuarios.insertar(usuario("b", Rol.ADMIN))
        val e = assertFailsWith<IllegalArgumentException> { reglas.validarCambioDeAcceso(admin, setOf(Rol.ADMIN), false, adminId = "a") }
        assertEquals("No puedes desactivar tu propia cuenta.", e.message)
    }

    @Test
    fun `siempre queda un administrador activo`() = runTest {
        val unico = usuario("a", Rol.ADMIN)
        usuarios.insertar(unico)
        val e = assertFailsWith<IllegalArgumentException> { reglas.validarCambioDeAcceso(unico, setOf(Rol.ACOPIADOR), true, adminId = "otro") }
        assertEquals("Debe quedar al menos un administrador activo.", e.message)
    }

    @Test
    fun `un acopiador con jornada abierta no se desactiva`() = runTest {
        val acopiador = usuario("c", Rol.ACOPIADOR)
        usuarios.insertar(acopiador)
        jornadas.insertar(Jornada("j", "c", "z1", "v", LocalDate(2026, 9, 22), 1L, null, SyncState.PENDING))
        assertFailsWith<IllegalArgumentException> { reglas.validarCambioDeAcceso(acopiador, setOf(Rol.ACOPIADOR), false, adminId = "a") }
    }

    @Test
    fun `proveedor no se combina con roles del personal`() = runTest {
        assertFailsWith<IllegalArgumentException> { reglas.validarAsignaciones(null, setOf(Rol.PROVEEDOR, Rol.ACOPIADOR), null, "p") }
    }

    @Test
    fun `tecnico de calidad necesita una zona activa`() = runTest {
        assertFailsWith<IllegalArgumentException> { reglas.validarAsignaciones(null, setOf(Rol.CALIDAD), null, null) }
        assertFailsWith<IllegalArgumentException> { reglas.validarAsignaciones(null, setOf(Rol.CALIDAD), "z2", null) }
        reglas.validarAsignaciones(null, setOf(Rol.CALIDAD), "z1", null)
    }

    @Test
    fun `una ficha vinculada a otra cuenta no se reutiliza`() = runTest {
        proveedores.insertar(Proveedor("p", "PRV-1", "Finca", "12345678", null, null, "z1", 1, 40.0,
            pe.ecolecta.domain.model.EstadoProveedor.ACTIVO, 0, SyncState.SYNCED, usuarioId = "otra"))
        assertFailsWith<IllegalArgumentException> { reglas.validarAsignaciones("nueva", setOf(Rol.PROVEEDOR), null, "p") }
        reglas.validarAsignaciones("otra", setOf(Rol.PROVEEDOR), null, "p")
    }

    @Test
    fun `una cuenta nueva tiene un solo rol`() = runTest {
        val e = assertFailsWith<IllegalArgumentException> { reglas.validarAsignaciones(null, setOf(Rol.ADMIN, Rol.ACOPIADOR), null, null) }
        assertEquals("Cada cuenta tiene un solo rol. Si una persona necesita otro perfil, créale una cuenta aparte.", e.message)
        reglas.validarAsignaciones(null, setOf(Rol.ACOPIADOR), null, null)
    }

    @Test
    fun `una cuenta anterior con varios roles se conserva si no se cambian`() = runTest {
        val legado = setOf(Rol.ADMIN, Rol.ACOPIADOR)
        reglas.validarAsignaciones("u1", legado, null, null, rolesAnteriores = legado)
    }

    @Test
    fun `una cuenta anterior con varios roles no puede cambiar a otra combinacion`() = runTest {
        assertFailsWith<IllegalArgumentException> {
            reglas.validarAsignaciones("u1", setOf(Rol.ADMIN, Rol.CALIDAD), "z1", null, rolesAnteriores = setOf(Rol.ADMIN, Rol.ACOPIADOR))
        }
        reglas.validarAsignaciones("u1", setOf(Rol.ADMIN), null, null, rolesAnteriores = setOf(Rol.ADMIN, Rol.ACOPIADOR))
    }
}
