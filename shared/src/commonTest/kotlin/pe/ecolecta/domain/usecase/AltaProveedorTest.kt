package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.runTest
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.usuario.DatosCuenta
import pe.ecolecta.domain.usecase.usuario.FichaNueva
import pe.ecolecta.domain.usecase.usuario.GuardarCuentaUseCase
import pe.ecolecta.domain.usecase.usuario.ReglasCuenta
import pe.ecolecta.presentation.admin.usuarios.siguienteCodigo
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNotEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** Alta de proveedores desde Usuarios y roles: ficha nueva con su cuenta, o ficha existente libre. */
class AltaProveedorTest {
    private val usuarios = FakeUsuarioRepository()
    private val proveedores = FakeProveedorRepository()
    private val auditoria = FakeAuditoriaRepository()
    private val zonas = object : ZonaRepository {
        val lista = listOf(Zona("faon", "FAON", true), Zona("vieja", "CERRADA", false))
        override fun observarTodas(): Flow<List<Zona>> = flowOf(lista)
        override fun observarActivas(): Flow<List<Zona>> = flowOf(lista.filter { it.activo })
        override suspend fun obtenerPorId(id: String) = lista.firstOrNull { it.id == id }
        override suspend fun insertar(zona: Zona) = Unit
        override suspend fun actualizar(zona: Zona) = Unit
        override suspend fun desactivar(id: String) = Unit
        override suspend fun contarProveedoresEnZona(id: String) = 0L
    }

    /** Como la transacción real: primero comprueba todo y solo entonces guarda; si falla no escribe nada. */
    private val cuentas = object : CuentasRepository {
        val asignadas = MutableStateFlow<Map<String, String>>(emptyMap())
        override fun observarZonasAsignadas(): Flow<Map<String, String>> = asignadas
        override suspend fun zonaAsignada(usuarioId: String) = asignadas.value[usuarioId]
        override suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?) {
            if (proveedorId != null) proveedores.vincularUsuario(proveedorId, usuarioId)
        }
        override suspend fun existeNombreZona(nombre: String, idExcluido: String) = false
        override suspend fun vehiculoEnJornadaAbierta(vehiculoId: String) = false
        override suspend fun crearCuenta(usuario: Usuario, zonaId: String?, fichaNueva: Proveedor?, fichaExistenteId: String?, auditorias: List<Auditoria>) {
            if (fichaExistenteId != null) {
                val ficha = proveedores.obtenerPorId(fichaExistenteId) ?: throw IllegalArgumentException("La ficha de proveedor ya no existe.")
                require(ficha.usuarioId == null) { "La ficha ${ficha.codigo} ya está vinculada a otra cuenta." }
            }
            usuarios.insertar(usuario)
            if (fichaNueva != null) proveedores.insertar(fichaNueva)
            if (fichaExistenteId != null) proveedores.vincularUsuario(fichaExistenteId, usuario.id)
            auditorias.forEach { auditoria.insertar(it) }
        }
    }

    private val guardar = GuardarCuentaUseCase(
        usuarios, cuentas, ReglasCuenta(usuarios, zonas, proveedores, FakeJornadaRepository()), proveedores,
        auditoria, Pbkdf2PinHasher(), FakeReloj(), FakeDeviceIdProvider(),
    )

    private fun ficha(codigo: String = "PRV-100", documento: String = "20123456789", zona: String = "faon") =
        FichaNueva(codigo = codigo, nombre = "Finca Santa Rosa", documento = documento, zonaId = zona, tachos = 2, capacidadTachoL = 40.0)

    private fun datos(
        username: String = "prv_santa",
        dni: String = "45678901",
        fichaNueva: FichaNueva? = ficha(),
        proveedorId: String? = null,
    ) = DatosCuenta(
        id = null, username = username, nombres = "Rosa Quispe Mamani", dni = dni, roles = setOf(Rol.PROVEEDOR), activo = true,
        zonaId = null, proveedorId = proveedorId, pin = "1234", confirmacionPin = "1234", fichaNueva = fichaNueva,
    )

    private suspend fun fichaExistente(id: String = "p-rosa", codigo: String = "PRV-003", usuarioId: String? = null) {
        proveedores.insertar(
            Proveedor.crear(id = id, codigo = codigo, nombres = "Rosa Ccapa", dni = "10000003", telefono = null, direccion = null,
                zonaId = "faon", updatedAt = 0L).getOrThrow().copy(usuarioId = usuarioId),
        )
    }

    @Test
    fun `proveedor nuevo crea cuenta y ficha propia vinculadas`() = runTest {
        val id = guardar(datos(), "admin").getOrThrow()

        val ficha = proveedores.obtenerPorUsuarioId(id)!!
        assertEquals("PRV-100", ficha.codigo)
        assertEquals("Finca Santa Rosa", ficha.nombres)
        assertEquals("20123456789", ficha.dni)
        assertEquals("faon", ficha.zonaId)
        assertEquals("Rosa Quispe Mamani", ficha.dueno)
        assertEquals("45678901", usuarios.obtenerPorId(id)!!.dni)
        assertEquals(listOf(AccionAuditoria.CREAR, AccionAuditoria.CREAR), auditoria.insertados.map { it.accion })
        assertEquals(setOf("usuario", "proveedor"), auditoria.insertados.map { it.entidad }.toSet())
    }

    @Test
    fun `dos proveedores nuevos tienen cada uno solo su ficha`() = runTest {
        val a = guardar(datos(), "admin").getOrThrow()
        val b = guardar(datos(username = "prv_lomas", dni = "45678902", fichaNueva = ficha("PRV-101", "20999888777")), "admin").getOrThrow()

        val fichaA = proveedores.obtenerPorUsuarioId(a)!!
        val fichaB = proveedores.obtenerPorUsuarioId(b)!!
        assertNotEquals(fichaA.id, fichaB.id)
        assertEquals(1, proveedores.observarTodos().first().count { it.usuarioId == a })
        assertEquals(1, proveedores.observarTodos().first().count { it.usuarioId == b })
    }

    @Test
    fun `codigo de ficha repetido no deja cuenta a medias`() = runTest {
        fichaExistente(codigo = "PRV-100")

        val error = guardar(datos(), "admin").exceptionOrNull()

        assertEquals("Ya existe una ficha con el código PRV-100.", error?.message)
        assertNull(usuarios.obtenerPorUsername("prv_santa"))
        assertEquals(0, auditoria.insertados.size)
    }

    @Test
    fun `documento de ficha repetido se rechaza`() = runTest {
        fichaExistente()

        val error = guardar(datos(fichaNueva = ficha(documento = "10000003")), "admin").exceptionOrNull()

        assertTrue(error?.message.orEmpty().startsWith("Ya existe una ficha con el documento 10000003."))
        assertNull(usuarios.obtenerPorUsername("prv_santa"))
    }

    @Test
    fun `usuario repetido no crea la ficha`() = runTest {
        guardar(datos(), "admin").getOrThrow()

        val error = guardar(datos(dni = "45678902", fichaNueva = ficha("PRV-101", "20999888777")), "admin").exceptionOrNull()

        assertEquals("Ya existe el usuario @prv_santa.", error?.message)
        assertEquals(1, proveedores.observarTodos().first().size)
    }

    @Test
    fun `dni de la persona repetido se rechaza`() = runTest {
        guardar(datos(), "admin").getOrThrow()

        val error = guardar(datos(username = "otro", fichaNueva = ficha("PRV-101", "20999888777")), "admin").exceptionOrNull()

        assertEquals("Ya existe una cuenta con el DNI 45678901.", error?.message)
    }

    @Test
    fun `documento y codigo mal formados o zona inactiva se rechazan`() = runTest {
        assertEquals(
            "El documento de la ficha debe ser un DNI de 8 dígitos o un RUC de 11.",
            guardar(datos(fichaNueva = ficha(documento = "123")), "admin").exceptionOrNull()?.message,
        )
        assertEquals("La zona CERRADA está inactiva.", guardar(datos(fichaNueva = ficha(zona = "vieja")), "admin").exceptionOrNull()?.message)
        assertTrue(proveedores.observarTodos().first().isEmpty())
    }

    @Test
    fun `ficha existente libre se vincula y la zona es la de la ficha`() = runTest {
        fichaExistente()

        val id = guardar(datos(fichaNueva = null, proveedorId = "p-rosa"), "admin").getOrThrow()

        assertEquals("p-rosa", proveedores.obtenerPorUsuarioId(id)?.id)
        assertEquals(1, proveedores.observarTodos().first().size)
    }

    @Test
    fun `ficha existente que ya tiene cuenta no se vuelve a entregar`() = runTest {
        fichaExistente(usuarioId = "otra-cuenta")

        val error = guardar(datos(fichaNueva = null, proveedorId = "p-rosa"), "admin").exceptionOrNull()

        assertEquals("La ficha PRV-003 ya está vinculada a otra cuenta.", error?.message)
        assertNull(usuarios.obtenerPorUsername("prv_santa"))
    }

    @Test
    fun `sin ficha nueva ni existente no se crea la cuenta de proveedor`() = runTest {
        val error = guardar(datos(fichaNueva = null), "admin").exceptionOrNull()

        assertEquals("Vincula la cuenta a una ficha de proveedor.", error?.message)
    }

    @Test
    fun `el codigo sugerido sigue al mayor PRV-NNN`() {
        assertEquals("PRV-007", siguienteCodigo(listOf("PRV-001", "PRV-006", "PRV-FAON-01")))
        assertEquals("PRV-001", siguienteCodigo(emptyList()))
    }
}
