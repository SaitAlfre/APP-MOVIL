package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import pe.ecolecta.data.security.InMemorySesionRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeRutaAcopioRepository
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.EstadoRutaAcopio
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.model.UbicacionAcopiador
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.EventoRuta
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerProveedorAsociadoUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerRutaAcopioUseCase
import kotlin.test.Test
import kotlin.test.assertIs
import kotlin.test.assertTrue

/**
 * Nunca depende de una tabla local de jornada: esa tabla vive solo en el dispositivo del ACOPIADOR y
 * no se sincroniza al del proveedor (dos dispositivos reales no comparten base de datos local) — la
 * única fuente de verdad para el proveedor es el documento remoto (ver [EventoRuta]).
 */
class ObtenerRutaAcopioUseCaseTest {
    private val proveedorRepository = FakeProveedorRepository()
    private val usuarioRepository = FakeUsuarioRepository()
    private val sesionRepository = InMemorySesionRepository()
    private val rutaAcopioRepository = FakeRutaAcopioRepository()
    private val reloj = FakeReloj()
    private val useCase = ObtenerRutaAcopioUseCase(
        ObtenerSesionUseCase(sesionRepository),
        ObtenerProveedorAsociadoUseCase(proveedorRepository),
        rutaAcopioRepository,
        reloj,
    )

    private suspend fun sembrarSesionProveedor(zonaId: String = "zona-1"): Proveedor {
        val hash = Pbkdf2PinHasher().crearHash("1234")
        val usuario = Usuario.crear(
            id = "prov-u1", username = "mquispe", nombres = "Mario Quispe", dni = "10000001",
            pinHash = hash.hash, pinSalt = hash.salt, activo = true, roles = listOf(Rol.PROVEEDOR), updatedAt = 0L,
        ).getOrThrow()
        usuarioRepository.insertar(usuario)
        val proveedor = Proveedor.crear(
            id = "p1", codigo = "PRV-001", nombres = "Mario Quispe", dni = "10000001",
            telefono = null, direccion = null, zonaId = zonaId, updatedAt = 0L, usuarioId = "prov-u1",
        ).getOrThrow()
        proveedorRepository.insertar(proveedor)
        sesionRepository.iniciar(Sesion(usuario, Rol.PROVEEDOR))
        return proveedor
    }

    private fun ubicacion(
        jornadaAbierta: Boolean = true,
        seguimientoActivo: Boolean = true,
        capturadaEn: Long = reloj.ahora().toEpochMilliseconds(),
    ) = UbicacionAcopiador(
        zonaId = "zona-1", zonaNombre = "Zona 1", acopiadorId = "acop-1", acopiadorNombre = "Juan",
        vehiculoId = "veh-1", vehiculoNombre = "Camión 1", jornadaId = "j1", jornadaAbiertaEn = 0L,
        fecha = "2026-01-15", lat = -12.0, lng = -77.0, precisionM = 8.0, capturadaEn = capturadaEn,
        secuenciaEn = capturadaEn, seguimientoActivo = seguimientoActivo, jornadaAbierta = jornadaAbierta,
    )

    @Test
    fun `sin sesion sin ruta asignada`() = runTest {
        assertIs<EstadoRutaAcopio.SinRutaAsignada>(useCase().first())
    }

    @Test
    fun `sin datos remotos todavia - nunca hubo jornada en la zona - jornada no iniciada`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.SinDatos)
        assertIs<EstadoRutaAcopio.JornadaNoIniciada>(useCase().first())
    }

    @Test
    fun `backend no conectado`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.NoConectado)
        assertIs<EstadoRutaAcopio.SeguimientoRemotoNoConectado>(useCase().first())
    }

    @Test
    fun `sin conexion a internet es distinto de backend no conectado`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.SinConexion)
        assertIs<EstadoRutaAcopio.SinConexion>(useCase().first())
    }

    @Test
    fun `jornada remota finalizada`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.Recibida(ubicacion(jornadaAbierta = false)))
        assertIs<EstadoRutaAcopio.JornadaFinalizada>(useCase().first())
    }

    @Test
    fun `seguimiento detenido pero jornada abierta`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.Recibida(ubicacion(seguimientoActivo = false)))
        assertIs<EstadoRutaAcopio.SeguimientoNoActivado>(useCase().first())
    }

    @Test
    fun `ubicacion reciente disponible se ve en vivo`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.Recibida(ubicacion(capturadaEn = reloj.ahora().toEpochMilliseconds() - 10_000)))
        val estado = useCase().first()
        assertIs<EstadoRutaAcopio.Disponible>(estado)
        assertTrue(estado.esVivo)
    }

    @Test
    fun `ubicacion vieja disponible no se muestra como en vivo`() = runTest {
        sembrarSesionProveedor()
        rutaAcopioRepository.emitir(EventoRuta.Recibida(ubicacion(capturadaEn = reloj.ahora().toEpochMilliseconds() - 600_000)))
        val estado = useCase().first()
        assertIs<EstadoRutaAcopio.Disponible>(estado)
        assertTrue(!estado.esVivo)
    }
}
