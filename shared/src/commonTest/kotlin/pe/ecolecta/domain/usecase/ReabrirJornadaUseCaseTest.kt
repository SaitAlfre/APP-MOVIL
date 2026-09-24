package pe.ecolecta.domain.usecase

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import pe.ecolecta.domain.ConfiguracionJornada
import pe.ecolecta.domain.ReaperturaJornadaException
import pe.ecolecta.domain.ZonaOcupadaException
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeReloj
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.security.Pbkdf2PinHasher
import pe.ecolecta.domain.usecase.auth.LoginOfflineUseCase
import pe.ecolecta.domain.usecase.jornada.AbrirJornadaUseCase
import pe.ecolecta.domain.usecase.jornada.CredencialAdmin
import pe.ecolecta.domain.usecase.jornada.ReabrirJornadaUseCase
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

class ReabrirJornadaUseCaseTest {
    private val reloj = FakeReloj()
    private val ahora = reloj.ahora().toEpochMilliseconds()
    private val jornadas = FakeJornadaRepository()
    private val enCurso = InMemoryJornadaEnCursoRepository()
    private val auditoria = FakeAuditoriaRepository()
    private val usuarios = FakeUsuarioRepository()
    private val pinHasher = Pbkdf2PinHasher()
    private val login = LoginOfflineUseCase(usuarios, pinHasher, auditoria, reloj, FakeDeviceIdProvider())
    private val abrir = AbrirJornadaUseCase(jornadas, enCurso, reloj)
    private val reabrir = ReabrirJornadaUseCase(
        jornadas, enCurso, auditoria, login, reloj, FakeDeviceIdProvider(), ConfiguracionJornada(plazoReaperturaMinutos = 30),
    )

    private suspend fun jornadaCerradaHace(minutos: Long, usuarioId: String = "u1", zonaId: String = "zona-1"): Jornada {
        val jornada = abrir(usuarioId, zonaId, "v1").getOrThrow()
        jornadas.cerrar(jornada.id, ahora - minutos * 60_000L)
        enCurso.limpiar()
        return jornada
    }

    private suspend fun sembrarUsuario(id: String, username: String, rol: Rol, pin: String = "1234") {
        val hash = pinHasher.crearHash(pin)
        usuarios.insertar(
            Usuario.crear(id, username, "Nombre $username", "4000000${id.last()}", hash.hash, hash.salt, true, listOf(rol), 0L).getOrThrow(),
        )
    }

    @Test
    fun `dentro del plazo reabre la misma jornada conserva entregas y deja auditoria`() = runTest {
        val jornada = jornadaCerradaHace(minutos = 10)
        val entregas = FakeEntregaRepository()
        entregas.sembrar(Entrega.crear("e1", jornada.id, "p1", "u1", "zona-1", "v1", 30.0, 1, null, 0, "d", null).getOrThrow())

        val resultado = reabrir(jornada.id, "u1", "Cerré por error antes de la última ruta")

        val reabierta = resultado.getOrThrow()
        assertEquals(jornada.id, reabierta.id, "debe ser la misma jornada, no una nueva")
        assertTrue(reabierta.estaAbierta)
        assertEquals(1, jornadas.observarTodas().first().size, "sigue habiendo una sola jornada del día")
        assertEquals(reabierta, enCurso.observar().first())
        assertEquals(1, entregas.observarPorJornada(jornada.id).first().size, "las entregas se conservan")
        val registro = auditoria.filtrar(accion = AccionAuditoria.REABRIR_JORNADA).single()
        assertEquals(jornada.id, registro.entidadId)
        assertEquals("cerradaEn=${ahora - 600_000L}", registro.valorAntes, "conserva la hora del cierre deshecho")
        assertEquals("Cerré por error antes de la última ruta", registro.motivo)
    }

    @Test
    fun `sin motivo suficiente no reabre`() = runTest {
        val jornada = jornadaCerradaHace(minutos = 5)

        val resultado = reabrir(jornada.id, "u1", "  error ")

        assertIs<ReaperturaJornadaException.MotivoObligatorio>(resultado.exceptionOrNull())
        assertFalse(jornadas.obtenerPorId(jornada.id)!!.estaAbierta)
        assertTrue(auditoria.insertados.isEmpty())
    }

    @Test
    fun `fuera de plazo exige autorizacion de administrador`() = runTest {
        val jornada = jornadaCerradaHace(minutos = 31)

        assertTrue(reabrir.requiereAutorizacion(jornadas.obtenerPorId(jornada.id)!!))
        val resultado = reabrir(jornada.id, "u1", "Cerré por error antes de la última ruta")

        assertIs<ReaperturaJornadaException.FueraDePlazo>(resultado.exceptionOrNull())
        assertFalse(jornadas.obtenerPorId(jornada.id)!!.estaAbierta)
    }

    @Test
    fun `fuera de plazo reabre con usuario y pin de un administrador local y lo audita`() = runTest {
        sembrarUsuario("a1", "admin", Rol.ADMIN, pin = "4321")
        val jornada = jornadaCerradaHace(minutos = 90)

        val resultado = reabrir(jornada.id, "u1", "Cerré por error antes de la última ruta", CredencialAdmin("admin", "4321"))

        assertTrue(resultado.getOrThrow().estaAbierta)
        val registro = auditoria.filtrar(accion = AccionAuditoria.REABRIR_JORNADA).single()
        assertTrue(registro.motivo!!.contains("Autorizada fuera de plazo por admin (a1)"))
    }

    @Test
    fun `autorizacion con pin incorrecto o sin rol admin se rechaza`() = runTest {
        sembrarUsuario("a1", "admin", Rol.ADMIN, pin = "4321")
        sembrarUsuario("u9", "otro_acop", Rol.ACOPIADOR, pin = "1111")
        val jornada = jornadaCerradaHace(minutos = 90)

        val pinMalo = reabrir(jornada.id, "u1", "Cerré por error antes de la ruta", CredencialAdmin("admin", "0000"))
        val noAdmin = reabrir(jornada.id, "u1", "Cerré por error antes de la ruta", CredencialAdmin("otro_acop", "1111"))

        assertIs<ReaperturaJornadaException.AutorizacionInvalida>(pinMalo.exceptionOrNull())
        assertIs<ReaperturaJornadaException.AutorizacionInvalida>(noAdmin.exceptionOrNull())
        assertFalse(jornadas.obtenerPorId(jornada.id)!!.estaAbierta)
    }

    @Test
    fun `no reabre jornadas de otro dia ni de otro usuario`() = runTest {
        val jornada = jornadaCerradaHace(minutos = 5)
        val manana = ReabrirJornadaUseCase(
            jornadas, enCurso, auditoria, login, FakeReloj(fecha = LocalDate(2026, 1, 16)), FakeDeviceIdProvider(), ConfiguracionJornada(),
        )

        assertIs<ReaperturaJornadaException.NoEsDeHoy>(manana(jornada.id, "u1", "Cerré por error antes de la ruta").exceptionOrNull())
        assertIs<ReaperturaJornadaException.NoPertenece>(reabrir(jornada.id, "u2", "Cerré por error antes de la ruta").exceptionOrNull())
    }

    @Test
    fun `no reabre si otro acopiador ocupo la zona`() = runTest {
        val jornada = jornadaCerradaHace(minutos = 5)
        abrir("u2", "zona-1", "v2").getOrThrow()

        val resultado = reabrir(jornada.id, "u1", "Cerré por error antes de la ruta")

        assertIs<ZonaOcupadaException>(resultado.exceptionOrNull())
        assertFalse(jornadas.obtenerPorId(jornada.id)!!.estaAbierta)
    }

    @Test
    fun `si no se puede auditar la reapertura se revierte`() = runTest {
        val jornada = jornadaCerradaHace(minutos = 5)
        val cerradaEn = jornadas.obtenerPorId(jornada.id)!!.cerradaEn
        val auditoriaRota = object : pe.ecolecta.domain.repository.AuditoriaRepository by auditoria {
            override suspend fun insertar(auditoria: pe.ecolecta.domain.model.Auditoria) = error("disco lleno")
        }
        val conAuditoriaRota = ReabrirJornadaUseCase(
            jornadas, enCurso, auditoriaRota, login, reloj, FakeDeviceIdProvider(), ConfiguracionJornada(),
        )

        val resultado = conAuditoriaRota(jornada.id, "u1", "Cerré por error antes de la ruta")

        assertIs<ReaperturaJornadaException.RegistroFallido>(resultado.exceptionOrNull())
        assertEquals(cerradaEn, jornadas.obtenerPorId(jornada.id)!!.cerradaEn, "vuelve a quedar cerrada con su hora original")
        assertNull(enCurso.observar().first())
        assertNotNull(cerradaEn)
    }
}
