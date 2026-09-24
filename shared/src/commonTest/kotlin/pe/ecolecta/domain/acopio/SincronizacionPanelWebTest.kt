package pe.ecolecta.domain.acopio

import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.flowOf
import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.fake.FakeAuditoriaRepository
import pe.ecolecta.domain.fake.FakeDeviceIdProvider
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeGestionPortalRepository
import pe.ecolecta.domain.fake.FakeJornadaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.fake.FakeRegistroAcopioRemoto
import pe.ecolecta.domain.fake.FakeServidorWeb
import pe.ecolecta.domain.fake.FakeSinRecojoRepository
import pe.ecolecta.domain.fake.FakeUsuarioRepository
import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Jornada
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.domain.model.Vehiculo
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.domain.repository.VehiculoRepository
import pe.ecolecta.domain.repository.ZonaRepository
import pe.ecolecta.domain.usecase.acopio.MarcarSinRecojoUseCase
import pe.ecolecta.domain.usecase.entrega.AnularEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.CorregirEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import pe.ecolecta.domain.usecase.entrega.ReglaEdicionEntrega
import pe.ecolecta.domain.usecase.sync.PreparadorEntregaServidorLocal
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import pe.ecolecta.domain.usecase.sync.VincularServidorUseCase
import pe.ecolecta.presentation.acopiador.sincronizacion.mensajeSincronizacion
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/**
 * Entregas del celular del acopiador → panel web (lo que consulta el administrador). Usa los casos de
 * uso reales de registro, corrección, anulación y sincronización; el panel es [FakeServidorWeb], con
 * las mismas reglas que el endpoint Laravel (probado aparte en web/tests/Feature/SincronizacionMovilTest).
 */
class SincronizacionPanelWebTest {
    private val hoy = LocalDate(2026, 9, 24)
    private val reloj = RelojLima("2026-09-24T14:05:00Z") // 09:05 en Lima
    private val dispositivo = FakeDeviceIdProvider("celular-acopiador")

    private val auditoria = FakeAuditoriaRepository()
    private val entregas = FakeEntregaRepository(auditoria)
    private val marcas = FakeSinRecojoRepository(auditoria)
    private val proveedores = FakeProveedorRepository()
    private val usuarios = FakeUsuarioRepository()
    private val jornadas = FakeJornadaRepository()
    private val zonas = object : ZonaRepository {
        private val todas = listOf(Zona("z1", "FAON-MARKAPAJO", true))
        override fun observarTodas(): Flow<List<Zona>> = flowOf(todas)
        override fun observarActivas(): Flow<List<Zona>> = flowOf(todas)
        override suspend fun obtenerPorId(id: String) = todas.firstOrNull { it.id == id }
        override suspend fun insertar(zona: Zona) = Unit
        override suspend fun actualizar(zona: Zona) = Unit
        override suspend fun desactivar(id: String) = Unit
        override suspend fun contarProveedoresEnZona(id: String) = 0L
    }
    private val vehiculos = object : VehiculoRepository {
        private val todos = listOf(Vehiculo("v1", "Camión 1", "V1A-123", true))
        override fun observarTodos(): Flow<List<Vehiculo>> = flowOf(todos)
        override fun observarActivos(): Flow<List<Vehiculo>> = flowOf(todos)
        override suspend fun obtenerPorId(id: String) = todos.firstOrNull { it.id == id }
        override suspend fun existePlaca(placa: String, idExcluido: String) = false
        override suspend fun insertar(vehiculo: Vehiculo) = Unit
        override suspend fun actualizar(vehiculo: Vehiculo) = Unit
        override suspend fun desactivar(id: String) = Unit
    }
    private val regla = ReglaEdicionEntrega(FakeGestionPortalRepository())
    private val registrar = RegistrarEntregaUseCase(entregas, proveedores, reloj, dispositivo)
    private val corregir = CorregirEntregaUseCase(entregas, reloj, dispositivo, regla)
    private val anular = AnularEntregaUseCase(entregas, reloj, dispositivo, regla)
    private val marcar = MarcarSinRecojoUseCase(jornadas, proveedores, entregas, marcas, reloj, dispositivo)

    private val panel = FakeServidorWeb()
    private val firestore = FakeRegistroAcopioRemoto(configurado = false)
    private val preparador = PreparadorEntregaServidorLocal(proveedores, usuarios, jornadas, zonas, vehiculos, auditoria)

    private fun sincronizador(remoto: FakeRegistroAcopioRemoto = firestore) =
        SincronizarRegistrosAcopioUseCase(entregas, marcas, proveedores, usuarios, remoto, panel, preparador)

    private val sincronizar = sincronizador()

    private suspend fun preparar(enlazado: Boolean = true) {
        proveedores.insertar(proveedorDePrueba("p1", codigo = "PRV-FAON-01"))
        proveedores.insertar(proveedorDePrueba("p2", codigo = "PRV-FAON-02"))
        proveedores.insertar(proveedorDePrueba("p9", codigo = "PRV-SOLO-EN-CELULAR"))
        usuarios.insertar(Usuario("u1", "acop_faon", "Juan Pérez", "1", "", "", true, listOf(Rol.ACOPIADOR), 0))
        jornadas.insertar(Jornada("j1", "u1", "z1", "v1", hoy, 1_790_000_000_000, null, SyncState.PENDING))
        if (enlazado) panel.vincular("u1", "acop_faon", "2468").getOrThrow()
    }

    @Test
    fun `una entrega pendiente llega al panel con sus datos reales y solo entonces queda sincronizada`() = runTest {
        preparar()
        val entrega = registrar("j1", "p1", "u1", "z1", "v1", 38.5, 2, "  tacho nuevo ").getOrThrow().entrega
        assertEquals(SyncState.PENDING, entregas.obtenerPorId(entrega.id)!!.syncState)
        assertTrue(panel.filas.value.isEmpty(), "nada llega al panel antes de sincronizar")

        val r = sincronizar()

        assertEquals(1, r.enviados)
        val fila = panel.filas.value.getValue(entrega.id)
        assertEquals("PRV-FAON-01", fila.proveedorCodigo)
        assertEquals("FAON-MARKAPAJO", fila.zonaNombre)
        assertEquals("V1A-123", fila.vehiculoPlaca)
        assertEquals("acop_faon", fila.acopiadorUsername)
        assertEquals("j1", fila.jornadaId)
        assertEquals(1_790_000_000_000, fila.jornadaAbiertaEn)
        assertEquals(38.5, fila.litros)
        assertEquals(2, fila.tachos)
        assertEquals("tacho nuevo", fila.observaciones)
        assertEquals(entrega.registradoEn, fila.registradoEn)
        assertEquals(SyncState.SYNCED, entregas.obtenerPorId(entrega.id)!!.syncState)
    }

    @Test
    fun `reintentar no duplica aunque la respuesta del primer envio se haya perdido`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 20.0, 1, null).getOrThrow()
        panel.perderRespuesta = true

        sincronizar()
        assertEquals(SyncState.PENDING, entregas.filtrar().single().syncState, "sin confirmación no se marca sincronizada")
        panel.perderRespuesta = false
        sincronizar()
        sincronizar()

        assertEquals(1, panel.filas.value.size)
        assertEquals(listOf("crear:${entregas.filtrar().single().id}"), panel.auditoria)
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `sin conexion queda pendiente con el motivo visible y se envia al volver`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 20.0, 1, null).getOrThrow()
        panel.enLinea.value = false

        val r = sincronizar()

        assertEquals(1, r.sinConexion)
        assertEquals("1 sin enviar: Sin conexión a internet. Se reintentarán solos.", mensajeSincronizacion(r))
        val local = entregas.filtrar().single()
        assertEquals(SyncState.PENDING, local.syncState)
        assertEquals("Sin conexión a internet", local.syncError)
        panel.enLinea.value = true
        sincronizar()
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `un rechazo del panel queda como error con el motivo del servidor y no se pierde el dato`() = runTest {
        preparar()
        registrar("j1", "p9", "u1", "z1", "v1", 12.0, 1, null).getOrThrow()

        val r = sincronizar()

        assertEquals(1, r.fallidos)
        assertEquals("1 rechazado(s) por el servidor: revisa el motivo en cada registro.", mensajeSincronizacion(r))
        val local = entregas.filtrar().single()
        assertEquals(SyncState.ERROR, local.syncState)
        assertEquals("El proveedor «PRV-SOLO-EN-CELULAR» no está registrado en el servidor.", local.syncError)
        assertTrue(panel.filas.value.isEmpty())

        // El administrador registra la ficha en el panel: el reintento automático la envía.
        panel.proveedoresRegistrados += "PRV-SOLO-EN-CELULAR"
        sincronizar()
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `una cuenta sin sesion con el panel queda pendiente sin contarse como error del servidor`() = runTest {
        preparar(enlazado = false)
        registrar("j1", "p1", "u1", "z1", "v1", 12.0, 1, null).getOrThrow()

        val r = sincronizar()

        assertEquals(1, r.sinEnlazar)
        assertEquals(0, r.fallidos, "no es un rechazo del servidor")
        assertEquals(setOf("Juan Pérez"), r.cuentasSinEnlazar)
        val local = entregas.filtrar().single()
        assertEquals(SyncState.PENDING, local.syncState, "el dato no fue rechazado: sigue en cola hasta enlazar")
        assertTrue(local.syncError!!.contains("iniciar sesión en este celular con conexión"))
        assertTrue(panel.filas.value.isEmpty())

        val mensaje = mensajeSincronizacion(r)
        assertFalse(mensaje.contains("error del servidor"), mensaje)
        assertFalse(mensaje.contains("se reintentar"), "no promete un reintento que no servirá: $mensaje")
        assertTrue(mensaje.contains("Juan Pérez debe iniciar sesión en este celular con conexión"), mensaje)
    }

    @Test
    fun `una entrega que quedo en error por falta de enlace pasa a pendiente y se envia al enlazar sin duplicar`() = runTest {
        preparar(enlazado = false)
        val entrega = registrar("j1", "p1", "u1", "z1", "v1", 12.0, 1, null).getOrThrow().entrega
        // Estado heredado de la versión anterior: ERROR por "sin sesión".
        entregas.registrarFalloSync(entrega.id, entrega.updatedAt, "sin sesión", definitivo = true)
        assertEquals(SyncState.ERROR, entregas.obtenerPorId(entrega.id)!!.syncState)

        sincronizar()
        assertEquals(SyncState.PENDING, entregas.obtenerPorId(entrega.id)!!.syncState)

        panel.vincular("u1", "acop_faon", "2468").getOrThrow()
        val r = sincronizar()
        sincronizar()

        assertEquals(1, r.enviados)
        assertEquals(listOf("crear:${entrega.id}"), panel.auditoria)
        assertEquals(SyncState.SYNCED, entregas.obtenerPorId(entrega.id)!!.syncState)
        assertNull(entregas.obtenerPorId(entrega.id)!!.syncError)
    }

    @Test
    fun `la entrega del acopiador se firma con su cuenta y la correccion de admin con la de admin`() = runTest {
        preparar()
        usuarios.insertar(Usuario("adm", "admin", "Administrador", "9", "", "", true, listOf(Rol.ADMIN), 0))
        panel.nombres["adm"] = "Administrador"
        val entrega = registrar("j1", "p1", "u1", "z1", "v1", 30.0, 1, null).getOrThrow().entrega
        sincronizar()
        assertEquals(listOf("${entrega.id}:u1"), panel.firmas, "el alta la firma el acopiador, no ADMIN")

        corregir(entrega.id, 27.0, 1, null, "Error de lectura", "adm").getOrThrow()
        val sinAdmin = sincronizar()
        assertEquals(1, sinAdmin.sinEnlazar, "ADMIN no enlazado: no se usa el token del acopiador para firmar su corrección")
        assertEquals(setOf("Administrador"), sinAdmin.cuentasSinEnlazar)
        assertEquals(30.0, panel.filas.value.getValue(entrega.id).litros)

        panel.vincular("adm", "admin", "1234").getOrThrow()
        sincronizar()
        assertEquals(listOf("${entrega.id}:u1", "${entrega.id}:adm"), panel.firmas)
        val fila = panel.filas.value.getValue(entrega.id)
        assertEquals(27.0, fila.litros)
        assertEquals("acop_faon", fila.acopiadorUsername, "la entrega sigue a nombre del acopiador")
        assertEquals(1, panel.filas.value.size)
    }

    @Test
    fun `al iniciar sesion la cuenta se enlaza en segundo plano y envia lo pendiente`() = runTest {
        preparar(enlazado = false)
        registrar("j1", "p1", "u1", "z1", "v1", 12.0, 1, null).getOrThrow()
        val vincular = VincularServidorUseCase(panel, { sincronizar() }, this)

        assertFalse(vincular.enlazado("u1"), "entrar offline no enlaza la cuenta")
        panel.enLinea.value = false
        vincular("u1", "acop_faon", "2468")
        testScheduler.advanceUntilIdle()
        assertTrue(vincular.errores.value.getValue("u1").contains("no respondió en http://10.0.2.2:8000"))
        panel.enLinea.value = true

        vincular("u1", "acop_faon", "0000")
        testScheduler.advanceUntilIdle()
        assertTrue(vincular.errores.value.getValue("u1").contains("no reconoce tu usuario o PIN"), "un PIN rechazado por el servidor se informa")
        assertTrue(panel.filas.value.isEmpty())

        vincular("u1", "acop_faon", "2468")
        testScheduler.advanceUntilIdle()
        assertNull(vincular.errores.value["u1"])
        assertTrue(vincular.enlazado("u1"))
        assertEquals(1, panel.filas.value.size)
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `correccion y anulacion llegan al panel sobre la misma fila con su motivo y sin borrar la auditoria local`() = runTest {
        preparar()
        val entrega = registrar("j1", "p1", "u1", "z1", "v1", 30.0, 1, null).getOrThrow().entrega
        sincronizar()

        corregir(entrega.id, 28.0, 1, null, "Medida mal leída", "u1").getOrThrow()
        sincronizar()
        assertEquals(28.0, panel.filas.value.getValue(entrega.id).litros)

        anular(entrega.id, "Registrada al proveedor equivocado", "u1").getOrThrow()
        sincronizar()

        assertEquals(1, panel.filas.value.size)
        val fila = panel.filas.value.getValue(entrega.id)
        assertTrue(fila.anulada)
        assertEquals("Registrada al proveedor equivocado", fila.motivo)
        assertEquals(
            listOf("crear:${entrega.id}", "corregir:${entrega.id}:Medida mal leída", "anular:${entrega.id}:Registrada al proveedor equivocado"),
            panel.auditoria,
        )
        assertEquals(
            listOf(AccionAuditoria.CREAR, AccionAuditoria.CORREGIR, AccionAuditoria.ANULAR),
            auditoria.filtrar(entidad = "entrega").map { it.accion },
        )
        assertEquals(SyncState.SYNCED, entregas.obtenerPorId(entrega.id)!!.syncState)
    }

    @Test
    fun `con panel y firestore solo queda sincronizada cuando ambos confirmaron`() = runTest {
        preparar()
        val celulares = FakeRegistroAcopioRemoto()
        celulares.rechazar = true
        val ambos = sincronizador(celulares)
        registrar("j1", "p1", "u1", "z1", "v1", 15.0, 1, null).getOrThrow()

        assertEquals(1, ambos().parciales)
        assertEquals(1, panel.filas.value.size, "el panel (oficial) ya la tiene")
        val parcial = entregas.filtrar().single()
        assertEquals(SyncState.ERROR, parcial.syncState, "pero falta la copia del proveedor")
        assertEquals(
            "Recibida por el panel web; falta la copia para el celular del proveedor: PERMISSION_DENIED",
            parcial.syncError,
            "el motivo no aparenta que todos los destinos la recibieron",
        )

        celulares.rechazar = false
        ambos()
        assertEquals(1, panel.filas.value.size)
        assertEquals(1, celulares.documentos.value.size)
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `si el panel la recibe pero firestore no tiene conexion queda pendiente y se reintenta sin duplicar en el panel`() = runTest {
        preparar()
        val celulares = FakeRegistroAcopioRemoto()
        celulares.enLinea.value = false
        val ambos = sincronizador(celulares)
        registrar("j1", "p1", "u1", "z1", "v1", 15.0, 1, null).getOrThrow()

        val r = ambos()

        assertEquals(1, r.parciales, "el panel la recibió: es un envío parcial, no una falta de conexión total")
        assertEquals(0, r.sinConexion)
        assertTrue(mensajeSincronizacion(r).contains("recibido(s) por el panel web, pero falta la copia"))
        val local = entregas.filtrar().single()
        assertEquals(SyncState.PENDING, local.syncState)
        assertEquals("Recibida por el panel web; falta la copia para el celular del proveedor: Sin conexión a internet", local.syncError)
        celulares.enLinea.value = true
        ambos()
        assertEquals(1, panel.filas.value.size)
        assertEquals(listOf("crear:${local.id}"), panel.auditoria, "el reenvío al panel no crea otra fila ni otra alta")
        assertEquals(SyncState.SYNCED, entregas.filtrar().single().syncState)
    }

    @Test
    fun `sin recojo no se envia al panel y sin firestore sigue guardado en el celular`() = runTest {
        preparar()
        marcar("j1", "p2", "u1", MotivoSinRecojo.SIN_LECHE, null).getOrThrow()

        val r = sincronizar()

        assertEquals(0, r.total)
        assertTrue(panel.filas.value.isEmpty())
        assertEquals(SyncState.PENDING, marcas.pendientesDeSincronizar().single().syncState)
    }

    @Test
    fun `sin ningun destino configurado no se marca nada como sincronizado`() = runTest {
        preparar()
        registrar("j1", "p1", "u1", "z1", "v1", 15.0, 1, null).getOrThrow()
        val nada = SincronizarRegistrosAcopioUseCase(entregas, marcas, proveedores, usuarios, firestore, FakeServidorWeb(configurado = false), preparador)

        nada()

        assertEquals(SyncState.PENDING, entregas.filtrar().single().syncState)
    }
}
