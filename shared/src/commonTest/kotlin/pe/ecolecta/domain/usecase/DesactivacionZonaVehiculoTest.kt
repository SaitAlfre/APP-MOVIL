package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.ZonaInvalidaException
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.CuentasRepository
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.usecase.vehiculo.ActualizarVehiculoUseCase
import pe.ecolecta.domain.usecase.zona.ActualizarZonaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertTrue

/** Zonas y vehículos en uso no se desactivan, y el mensaje dice qué lo impide. */
class DesactivacionZonaVehiculoTest {
    private val jornadas = FakeJornadaRepository()
    private var proveedoresActivosEnZona = 0L
    private val zonasAsignadas = MutableStateFlow<Map<String, String>>(emptyMap())
    private var vehiculoEnJornada = false

    private val zonas = object : ZonaRepository {
        var zona = Zona("z1", "FAON", true)
        override fun observarTodas(): Flow<List<Zona>> = flowOf(listOf(zona))
        override fun observarActivas(): Flow<List<Zona>> = flowOf(listOf(zona).filter { it.activo })
        override suspend fun obtenerPorId(id: String) = zona.takeIf { it.id == id }
        override suspend fun insertar(zona: Zona) = Unit
        override suspend fun actualizar(zona: Zona) { this.zona = zona }
        override suspend fun desactivar(id: String) = Unit
        override suspend fun contarProveedoresEnZona(id: String) = proveedoresActivosEnZona
    }

    private val vehiculos = object : VehiculoRepository {
        var vehiculo = Vehiculo("v1", "Camión 1", "V1A-123", true)
        override fun observarTodos(): Flow<List<Vehiculo>> = flowOf(listOf(vehiculo))
        override fun observarActivos(): Flow<List<Vehiculo>> = flowOf(listOf(vehiculo))
        override suspend fun obtenerPorId(id: String) = vehiculo.takeIf { it.id == id }
        override suspend fun existePlaca(placa: String, idExcluido: String) = false
        override suspend fun insertar(vehiculo: Vehiculo) = Unit
        override suspend fun actualizar(vehiculo: Vehiculo) { this.vehiculo = vehiculo }
        override suspend fun desactivar(id: String) = Unit
    }

    private val cuentas = object : CuentasRepository {
        override fun observarZonasAsignadas(): Flow<Map<String, String>> = zonasAsignadas
        override suspend fun zonaAsignada(usuarioId: String): String? = zonasAsignadas.value[usuarioId]
        override suspend fun guardarAsignaciones(usuarioId: String, zonaId: String?, proveedorId: String?) = Unit
        override suspend fun existeNombreZona(nombre: String, idExcluido: String) = false
        override suspend fun vehiculoEnJornadaAbierta(vehiculoId: String) = vehiculoEnJornada
        override suspend fun crearCuenta(usuario: pe.ecolecta.domain.model.Usuario, zonaId: String?, fichaNueva: pe.ecolecta.domain.model.Proveedor?, fichaExistenteId: String?, auditorias: List<pe.ecolecta.domain.model.Auditoria>) = Unit
    }

    private val actualizarZona = ActualizarZonaUseCase(zonas, jornadas, cuentas)
    private val actualizarVehiculo = ActualizarVehiculoUseCase(vehiculos, cuentas)

    @Test
    fun `zona con proveedores activos no se desactiva`() = runTest {
        proveedoresActivosEnZona = 2

        assertIs<ZonaInvalidaException.ConProveedoresActivos>(actualizarZona("z1", "FAON", false).exceptionOrNull())
        assertTrue(zonas.zona.activo)
    }

    @Test
    fun `zona con jornada abierta no se desactiva`() = runTest {
        jornadas.insertar(Jornada("j1", "u1", "z1", "v1", LocalDate(2026, 9, 23), 0L, null, SyncState.PENDING))

        val error = actualizarZona("z1", "FAON", false).exceptionOrNull()

        assertEquals("No se puede desactivar: hay una jornada abierta en esta zona.", error?.message)
    }

    @Test
    fun `zona asignada a personal no se desactiva y dice cuantas cuentas`() = runTest {
        zonasAsignadas.value = mapOf("acop" to "z1", "calidad" to "z1")

        val error = actualizarZona("z1", "FAON", false).exceptionOrNull()

        assertEquals("No se puede desactivar: 2 cuenta(s) tienen esta zona asignada. Reasígnalas en Usuarios y roles.", error?.message)
    }

    @Test
    fun `zona sin uso se desactiva`() = runTest {
        assertTrue(actualizarZona("z1", "FAON", false).isSuccess)
        assertEquals(false, zonas.zona.activo)
    }

    @Test
    fun `vehiculo en jornada abierta no se desactiva pero se puede renombrar`() = runTest {
        vehiculoEnJornada = true

        val error = actualizarVehiculo("v1", "Camión 1", "V1A-123", false).exceptionOrNull()
        assertEquals("No se puede desactivar: el vehículo está en una jornada abierta.", error?.message)
        assertTrue(vehiculos.vehiculo.activo)

        assertTrue(actualizarVehiculo("v1", "Camión uno", "V1A-123", true).isSuccess)
    }
}
