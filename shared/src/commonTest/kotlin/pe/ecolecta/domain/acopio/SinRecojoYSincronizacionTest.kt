package pe.ecolecta.domain.acopio

import kotlinx.coroutines.flow.first
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeGestionPortalRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeRegistroAcopioRemoto
import pe.ecolecta.domain.fake.FakeSinRecojoRepository
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.repository.EventoRegistrosRemotos
import pe.ecolecta.domain.usecase.acopio.DeshacerSinRecojoUseCase
import pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase
import pe.ecolecta.domain.usecase.acopio.SinRecojoException
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega
import pe.ecolecta.domain.usecase.jornada.CerrarJornadaUseCase
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import pe.ecolecta.data.security.InMemoryJornadaEnCursoRepository
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertIs
import kotlin.test.assertNotNull
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Dos celulares y un servidor: el del acopiador (repositorios locales + sincronizador) y el del
 * proveedor, que solo conoce su propia ficha (con OTRO id local, mismo código) y lo que recibe.
 */
class SinRecojoYSincronizacionTest {
    private val hoy = LocalDate(2026, 9, 24)
    private val reloj = RelojLima("2026-09-24T14:05:00Z") // 09:05 en Lima
    private val dispositivo = FakeDeviceIdProvider("celular-acopiador")

    // --- Celular del acopiador ---
    private val auditoria = FakeAuditoriaRepository()
    private val entregas = FakeEntregaRepository(auditoria)
    private val marcas = FakeSinRecojoRepository(auditoria)
    private val proveedores = FakeProveedorRepository()
    private val usuarios = FakeUsuarioRepository()
    private val jornadas = FakeJornadaRepository()
    private val regla = ReglaEdicionEntrega(FakeGestionPortalRepository())
    private val registrar = RegistrarEntregaUseCase(entregas, proveedores, reloj, dispositivo)
    private val marcar = MarcarSinRecojoUseCase(jornadas, proveedores, entregas, marcas, reloj, dispositivo)
    private val deshacer = DeshacerSinRecojoUseCase(jornadas, marcas, reloj, dispositivo)
    private val corregir = CorregirEntregaUseCase(entregas, reloj, dispositivo, regla)
    private val anular = AnularEntregaUseCase(entregas, reloj, dispositivo, regla)

    // --- Servidor ---
    private val servidor = FakeRegistroAcopioRemoto()
    private val sincronizar = SincronizarRegistrosAcopioUseCase(entregas, marcas, proveedores, usuarios, servidor)

    // --- Celular del proveedor PRV-FAON-01 (su ficha local tiene otro id) ---
    private val fichaProveedor = proveedorDePrueba("id-en-celular-proveedor", codigo = "PRV-FAON-01")

    private suspend fun preparar(jornadaAbierta: Boolean = true) {
        proveedores.insertar(proveedorDePrueba("p1", codigo = "PRV-FAON-01"))
        proveedores.insertar(proveedorDePrueba("p2", codigo = "PRV-FAON-02"))
        proveedores.insertar(proveedorDePrueba("p3", zonaId = "z2", codigo = "PRV-MORO-01"))
        usuarios.insertar(Usuario("u1", "acop_faon", "Juan Pérez", "1", "", "", true, listOf(Rol.ACOPIADOR), 0))
        jornadas.insertar(
            Jornada("j1", "u1", "z1", "v1", hoy, reloj.ahora().toEpochMilliseconds(), if (jornadaAbierta) null else 1L, SyncState.PENDING),
        )
    }

    /** Lo que ve el proveedor en su celular tras recibir del servidor (mismo cálculo que su portal). */
    private suspend fun vistaProveedor(): List<DiaAcopio> {
        val evento = servidor.observarDeProveedor(fichaProveedor.codigo).first()
        val recibidos = (evento as? EventoRegistrosRemotos.Recibidos)?.registros.orEmpty()
        val registros = combinarRegistrosProveedor(fichaProveedor, emptyList(), emptyList(), recibidos, remotoDisponible = true)
        return construirDiasDelCiclo(cicloAcopioDe(hoy, "FAON"), registros.recojos, registros.sinRecojos)
    }

    // ------------------------------------------------------------------ Sin recojo

    @Test
    fun `marcar sin recojo guarda el motivo, lo audita y queda pendiente de sincronizar`() = runTest {
        preparar()
        val marca = marcar("j1", "p2", "u1", MotivoSinRecojo.PROVEEDOR_NO_DISPONIBLE, "  viajó a Juliaca ").getOrThrow()

        assertEquals(hoy, marca.fecha)
        assertEquals("Proveedor no disponible · viajó a Juliaca", marca.textoMotivo)
        assertEquals(SyncState.PENDING, marca.syncState)
        val registro = auditoria.filtrar(entidad = "sin_recojo").single()
        assertEquals(AccionAuditoria.CREAR, registro.accion)
        assertEquals("u1", registro.usuarioId)
    }

    @Test
    fun `sin recojo se rechaza fuera de las reglas`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 20.0, 1, null).getOrThrow()

        assertIs<SinRecojoException.YaTieneEntrega>(marcar("j1", "p1", "u1", MotivoSinRecojo.SIN_LECHE, null).exceptionOrNull())
        assertIs<SinRecojoException.ProveedorFueraDeZona>(marcar("j1", "p3", "u1", MotivoSinRecojo.SIN_LECHE, null).exceptionOrNull())
        assertIs<SinRecojoException.JornadaNoPertenece>(marcar("j1", "p2", "otro", MotivoSinRecojo.SIN_LECHE, null).exceptionOrNull())
        marcar("j1", "p2", "u1", MotivoSinRecojo.SIN_LECHE, null).getOrThrow()
        assertIs<SinRecojoException.YaMarcado>(marcar("j1", "p2", "u1", MotivoSinRecojo.OTRO, null).exceptionOrNull())
        assertEquals(1, marcas.marcas.value.size)
    }

    @Test
    fun `deshacer sin recojo antes del cierre lo conserva como deshecho y auditado`() = runTest {
        preparar()
        val marca = marcar("j1", "p2", "u1", MotivoSinRecojo.NO_SE_PUDO_LLEGAR, null).getOrThrow()

        deshacer(marca.id, "u1").getOrThrow()

        val guardada = marcas.obtenerPorId(marca.id)!!
        assertTrue(guardada.deshecha, "no se borra: queda en el historial")
        assertNull(marcas.vigentePara("p2", hoy))
        assertEquals(listOf(AccionAuditoria.CREAR, AccionAuditoria.ANULAR), auditoria.filtrar(entidad = "sin_recojo").map { it.accion })
        // Ya puede volver a marcarse o registrarse normalmente.
        assertTrue(marcar("j1", "p2", "u1", MotivoSinRecojo.SIN_LECHE, null).isSuccess)
    }

    @Test
    fun `despues del cierre de jornada no se marca ni se deshace sin reabrir`() = runTest {
        preparar()
        val marca = marcar("j1", "p2", "u1", MotivoSinRecojo.SIN_LECHE, null).getOrThrow()
        CerrarJornadaUseCase(jornadas, InMemoryJornadaEnCursoRepository(), reloj, auditoria, dispositivo)("j1").getOrThrow()

        assertIs<SinRecojoException.JornadaCerrada>(deshacer(marca.id, "u1").exceptionOrNull())
        assertIs<SinRecojoException.JornadaCerrada>(marcar("j1", "p1", "u1", MotivoSinRecojo.OTRO, null).exceptionOrNull())
        assertTrue(marcas.obtenerPorId(marca.id)!!.vigente, "la marca cerrada sigue vigente")
    }

    // ------------------------------------------------------------------ Sincronización

    @Test
    fun `el proveedor recibe la entrega cuando se sincroniza y no antes`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 35.5, 2, null).getOrThrow()

        assertEquals(EstadoRecojo.PENDIENTE, vistaProveedor().first { it.fecha == hoy }.estado, "aún no enviado: el proveedor no lo ve")

        val resultado = sincronizar()

        assertEquals(1, resultado.enviados)
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
        val dia = vistaProveedor().first { it.fecha == hoy }
        assertEquals(EstadoRecojo.REGISTRADO, dia.estado)
        assertEquals(35.5, dia.totalLitros)
        assertEquals(EstadoSincronizacion.SINCRONIZADO, dia.sincronizacion)
        val doc = servidor.documentos.value.values.single()
        assertEquals("PRV-FAON-01", doc.proveedorCodigo)
        assertEquals("2026-09-24", doc.fecha)
        assertEquals("Juan Pérez", doc.acopiadorNombre)
    }

    @Test
    fun `sin conexion conserva el dato local con estado honesto y al reintentar no duplica`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 40.0, 1, null).getOrThrow()
        servidor.enLinea.value = false

        val sinRed = sincronizar()

        assertEquals(1, sinRed.sinConexion)
        val local = entregas.filtrar().single()
        assertEquals(SyncState.PENDING, local.syncState, "sin red no es un error: sigue pendiente")
        assertEquals("Sin conexión a internet", local.syncError)
        assertIs<EventoRegistrosRemotos.SinConexion>(servidor.observarDeProveedor("PRV-FAON-01").first())

        servidor.enLinea.value = true
        sincronizar()
        sincronizar()
        sincronizar()

        assertEquals(1, servidor.documentos.value.size, "un solo documento por entrega")
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
        assertEquals(40.0, vistaProveedor().first { it.fecha == hoy }.totalLitros)
    }

    @Test
    fun `si la respuesta se pierde el reintento sobrescribe el mismo documento`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 18.0, 1, null).getOrThrow()
        servidor.perderRespuesta = true
        sincronizar()
        assertEquals(SyncState.PENDING, entregas.filtrar().single().syncState)

        servidor.perderRespuesta = false
        sincronizar()

        assertEquals(2, servidor.escrituras)
        assertEquals(1, servidor.documentos.value.size)
        assertEquals(18.0, vistaProveedor().first { it.fecha == hoy }.totalLitros, "no se suma dos veces")
    }

    @Test
    fun `varias entregas del dia llegan separadas y el proveedor ve el total diario`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 20.0, 1, null).getOrThrow()
        registrar("j1", "p1", "u1", "z1", "v1", 15.5, 1, null).getOrThrow()
        sincronizar()

        val dia = vistaProveedor().first { it.fecha == hoy }
        assertEquals(2, dia.recojos.size)
        assertEquals(35.5, dia.totalLitros)
    }

    @Test
    fun `correcciones y anulaciones quedan auditadas y se reenvian sobre el mismo documento`() = runTest {
        preparar()
        val entrega = registrar("j1", "p1", "u1", "z1", "v1", 30.0, 1, null).getOrThrow().entrega
        sincronizar()

        corregir(entrega.id, 28.0, 1, null, "Medida mal leída", "u1").getOrThrow()
        assertEquals(SyncState.PENDING, entregas.obtenerPorId(entrega.id)!!.syncState, "la corrección vuelve a quedar pendiente")
        sincronizar()
        assertEquals(28.0, vistaProveedor().first { it.fecha == hoy }.totalLitros)

        anular(entrega.id, "Registrada al proveedor equivocado", "u1").getOrThrow()
        sincronizar()

        assertEquals(1, servidor.documentos.value.size)
        assertTrue(servidor.documentos.value.values.single().anulada)
        assertEquals(EstadoRecojo.PENDIENTE, vistaProveedor().first { it.fecha == hoy }.estado)
        assertEquals(
            listOf(AccionAuditoria.CREAR, AccionAuditoria.CORREGIR, AccionAuditoria.ANULAR),
            auditoria.filtrar(entidad = "entrega").map { it.accion },
        )
        assertEquals("Medida mal leída", auditoria.filtrar(accion = AccionAuditoria.CORREGIR).single().motivo)
    }

    @Test
    fun `una correccion hecha mientras se envia no se da por sincronizada`() = runTest {
        preparar()
        val entrega = registrar("j1", "p1", "u1", "z1", "v1", 30.0, 1, null).getOrThrow().entrega
        servidor.alPublicar = {
            servidor.alPublicar = null
            corregir(entrega.id, 25.0, 1, null, "Corrección en la puerta", "u1").getOrThrow()
        }

        sincronizar()
        assertEquals(SyncState.PENDING, entregas.obtenerPorId(entrega.id)!!.syncState)

        sincronizar()
        assertEquals(SyncState.SYNCED, entregas.obtenerPorId(entrega.id)!!.syncState)
        assertEquals(25.0, servidor.documentos.value.values.single().litros)
    }

    @Test
    fun `un rechazo del servidor queda como error visible y se reintenta`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 10.0, 1, null).getOrThrow()
        servidor.rechazar = true

        val r = sincronizar()

        assertEquals(1, r.fallidos)
        assertEquals(SyncState.ERROR, entregas.filtrar().single().syncState)
        servidor.rechazar = false
        sincronizar()
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `sin backend configurado nada se marca como sincronizado`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 10.0, 1, null).getOrThrow()
        val local = FakeRegistroAcopioRemoto(configurado = false)

        SincronizarRegistrosAcopioUseCase(entregas, marcas, proveedores, usuarios, local)()

        assertEquals(0, local.escrituras)
        val fila = construirFilasAcopio(cicloAcopioDe(hoy, "FAON"), "z1", proveedores.observarActivosPorZona("z1").first(), entregas.filtrar(), emptyList(), remotoDisponible = false)
        assertEquals(EstadoSincronizacion.EN_ESTE_CELULAR, fila.first { it.proveedor.id == "p1" }.dia(hoy)!!.sincronizacion)
    }

    @Test
    fun `sin recojo y su anulacion tambien llegan al proveedor`() = runTest {
        preparar()
        val marcaProv1 = marcar("j1", "p1", "u1", MotivoSinRecojo.SIN_LECHE, null).getOrThrow()
        sincronizar()
        val dia = vistaProveedor().first { it.fecha == hoy }
        assertEquals(EstadoRecojo.SIN_RECOJO, dia.estado)
        assertEquals("No tenía leche", dia.sinRecojo!!.textoMotivo)

        deshacer(marcaProv1.id, "u1").getOrThrow()
        sincronizar()

        assertEquals(EstadoRecojo.PENDIENTE, vistaProveedor().first { it.fecha == hoy }.estado)
        assertEquals(1, servidor.documentos.value.size)
        assertNotNull(marcas.obtenerPorId(marcaProv1.id))
    }

    // ------------------------------------------------------------------ Privacidad

    @Test
    fun `el proveedor solo recibe y combina sus propios registros`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 11.0, 1, null).getOrThrow()
        registrar("j1", "p2", "u1", "z1", "v1", 60.0, 1, null).getOrThrow()
        marcar("j1", "p2", "u1", MotivoSinRecojo.OTRO, "dato privado").exceptionOrNull() // p2 ya tiene entrega: rechazado
        sincronizar()

        val recibidos = (servidor.observarDeProveedor("PRV-FAON-01").first() as EventoRegistrosRemotos.Recibidos).registros
        assertEquals(setOf("PRV-FAON-01"), recibidos.map { it.proveedorCodigo }.toSet())

        // Aunque llegaran datos ajenos (servidor mal configurado, caché compartida), se descartan.
        val todos = servidor.documentos.value.values.toList()
        val ajenaLocal = entregaDePrueba("otra", "otro-proveedor", 50.0, "2026-09-24T12:00:00Z")
        val combinado = combinarRegistrosProveedor(fichaProveedor, listOf(ajenaLocal), emptyList(), todos, remotoDisponible = true)
        assertEquals(listOf(11.0), combinado.entregas.map { it.litros })
        assertTrue(combinado.entregas.all { it.proveedorId == fichaProveedor.id })
        assertEquals(listOf(11.0), combinado.recojos.map { it.litros })
    }
}
