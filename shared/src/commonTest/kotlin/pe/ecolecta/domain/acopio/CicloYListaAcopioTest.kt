package pe.ecolecta.domain.acopio

import kotlinx.coroutines.test.runTest
import kotlinx.datetime.LocalDate
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.Reloj
import pe.ecolecta.domain.fake.FakeEntregaRepository
import pe.ecolecta.domain.fake.FakeProveedorRepository
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.usecase.entrega.RegistrarEntregaUseCase
import pe.ecolecta.presentation.acopiador.lista.FiltroLista
import pe.ecolecta.presentation.acopiador.lista.filtrarFilas
import pe.ecolecta.presentation.design.formatearHoraAcopio
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertNull
import kotlin.test.assertTrue
import kotlin.time.Instant

/**
 * Reloj que arranca en una hora dada (UTC; Perú es UTC-5) y avanza 1 s en cada lectura, como el real:
 * así dos operaciones seguidas nunca comparten `updatedAt`.
 */
internal class RelojLima(isoUtc: String) : Reloj {
    private var actual = Instant.parse(isoUtc).toEpochMilliseconds()
    override fun hoy(): LocalDate = fechaAcopioDe(actual)
    override fun ahora(): Instant = Instant.fromEpochMilliseconds(actual).also { actual += 1_000 }
}

internal fun proveedorDePrueba(id: String, zonaId: String = "z1", estado: EstadoProveedor = EstadoProveedor.ACTIVO, codigo: String = "PRV-$id") = Proveedor(
    id = id, codigo = codigo, nombres = "Finca $id", dni = id, telefono = null, direccion = null, zonaId = zonaId,
    tachos = 2, capacidadTachoL = 40.0, estado = estado, updatedAt = 0, syncState = SyncState.SYNCED,
)

internal fun entregaDePrueba(id: String, proveedorId: String, litros: Double, isoUtc: String, syncState: SyncState = SyncState.PENDING, anulada: Boolean = false) =
    Entrega.crear(id, "j1", proveedorId, "u1", "z1", "v1", litros, 1, null, Instant.parse(isoUtc).toEpochMilliseconds(), "d", null)
        .getOrThrow().copy(syncState = syncState, anulada = anulada)

class CicloYListaAcopioTest {
    // 24/09/2026 es el día 267 del año → ciclo 45, día 3: del 22 al 27 de septiembre.
    private val hoy = LocalDate(2026, 9, 24)
    private val ciclo = cicloAcopioDe(hoy, "FAON")

    @Test
    fun `el ciclo muestra exactamente las seis fechas vigentes`() {
        assertEquals(6, ciclo.dias.size)
        assertEquals((22..27).map { LocalDate(2026, 9, it) }, ciclo.dias)
        assertEquals(45, ciclo.numero)
        assertEquals(3, ciclo.dia)
        assertEquals(ciclo.dias.toSet().size, 6, "sin fechas repetidas")
    }

    @Test
    fun `al terminar el dia 6 la lista pasa al ciclo siguiente sin saltar ni repetir dias`() {
        val ultimo = cicloAcopioDe(LocalDate(2026, 9, 27), "FAON")
        val siguiente = cicloAcopioDe(LocalDate(2026, 9, 28), "FAON")
        assertEquals(6, ultimo.dia)
        assertEquals(1, siguiente.dia)
        assertEquals(ultimo.numero + 1, siguiente.numero)
        assertEquals(LocalDate(2026, 9, 28), siguiente.inicio)
        assertEquals(LocalDate(2026, 10, 3), siguiente.fin)
        assertTrue(ultimo.dias.intersect(siguiente.dias.toSet()).isEmpty())
        // Todos los días de un ciclo devuelven el mismo ciclo (mismas 6 fechas para cualquier pantalla).
        ciclo.dias.forEach { assertEquals(ciclo.dias, cicloAcopioDe(it, "FAON").dias) }
    }

    @Test
    fun `el dia de acopio se decide con la hora de Peru y no con la del telefono`() {
        // 03:30 UTC del 25 = 22:30 del 24 en Lima: la entrega es del día 24.
        assertEquals(LocalDate(2026, 9, 24), fechaAcopioDe(Instant.parse("2026-09-25T03:30:00Z").toEpochMilliseconds()))
        // 05:00 UTC del 25 = 00:00 del 25 en Lima.
        assertEquals(LocalDate(2026, 9, 25), fechaAcopioDe(Instant.parse("2026-09-25T05:00:00Z").toEpochMilliseconds()))
        assertEquals("22:30", formatearHoraAcopio(Instant.parse("2026-09-25T03:30:00Z").toEpochMilliseconds()))
    }

    @Test
    fun `el filtro de zona solo muestra proveedores activos asignados a la zona de la jornada`() {
        val proveedores = listOf(
            proveedorDePrueba("a"),
            proveedorDePrueba("b", zonaId = "z2"),
            proveedorDePrueba("c", estado = EstadoProveedor.SUSPENDIDO),
            proveedorDePrueba("d"),
        )
        val filas = construirFilasAcopio(ciclo, "z1", proveedores, emptyList(), emptyList(), remotoDisponible = true)
        assertEquals(listOf("a", "d"), filas.map { it.proveedor.id })
    }

    @Test
    fun `registrar litros actualiza la celda de la fecha correcta con su hora`() = runTest {
        val entregas = FakeEntregaRepository()
        val proveedores = FakeProveedorRepository().apply { insertar(proveedorDePrueba("a")) }
        val reloj = RelojLima("2026-09-24T11:38:00Z") // 06:38 en Lima
        RegistrarEntregaUseCase(entregas, proveedores, reloj, object : DeviceIdProvider { override fun obtenerId() = "d" })(
            "j1", "a", "u1", "z1", "v1", 42.5, 2, null,
        ).getOrThrow()

        val fila = construirFilasAcopio(ciclo, "z1", listOf(proveedorDePrueba("a")), entregas.filtrar(), emptyList(), true).single()
        val dia = fila.dia(hoy)!!
        assertEquals(EstadoRecojo.REGISTRADO, dia.estado)
        assertEquals(42.5, dia.totalLitros)
        assertEquals("06:38", formatearHoraAcopio(dia.horaUltima!!))
        assertEquals(2, ciclo.dias.indexOf(dia.fecha), "es la columna Día 3")
        fila.dias.filter { it.fecha != hoy }.forEach { assertEquals(EstadoRecojo.PENDIENTE, it.estado) }
    }

    @Test
    fun `varias entregas del mismo dia conservan su detalle y suman correctamente`() {
        val entregas = listOf(
            entregaDePrueba("e2", "a", 12.25, "2026-09-24T15:10:00Z"),
            entregaDePrueba("e1", "a", 30.5, "2026-09-24T11:00:00Z"),
            entregaDePrueba("e3", "a", 5.0, "2026-09-24T16:00:00Z", anulada = true),
            entregaDePrueba("e4", "a", 20.0, "2026-09-23T12:00:00Z"),
        )
        val fila = construirFilasAcopio(ciclo, "z1", listOf(proveedorDePrueba("a")), entregas, emptyList(), true).single()
        val dia = fila.dia(hoy)!!
        assertEquals(listOf("e1", "e2"), dia.recojos.map { it.id }, "cada entrega por separado, en orden de hora; la anulada no cuenta")
        assertEquals(42.75, dia.totalLitros)
        assertEquals(20.0, fila.dia(LocalDate(2026, 9, 23))!!.totalLitros)
        assertEquals(62.75, fila.totalCicloLitros)
    }

    @Test
    fun `una celda vacia sigue pendiente y sin recojo exige una marca con motivo`() {
        val marca = MarcaSinRecojo(
            "m1", "j1", "b", "u1", "z1", hoy, MotivoSinRecojo.NO_SE_PUDO_LLEGAR, null, 1, false, null, SyncState.PENDING, null, 0, 1,
        )
        val deshecha = marca.copy(id = "m2", proveedorId = "c", deshecha = true)
        val filas = construirFilasAcopio(
            ciclo, "z1", listOf(proveedorDePrueba("a"), proveedorDePrueba("b"), proveedorDePrueba("c")),
            emptyList(), listOf(marca, deshecha), remotoDisponible = true,
        )
        val a = filas.first { it.proveedor.id == "a" }.dia(hoy)!!
        assertEquals(EstadoRecojo.PENDIENTE, a.estado, "vacío = todavía no registrado, no cero ni sin recojo")
        assertEquals(0.0, a.totalLitros)
        assertNull(a.sincronizacion)
        val b = filas.first { it.proveedor.id == "b" }.dia(hoy)!!
        assertEquals(EstadoRecojo.SIN_RECOJO, b.estado)
        assertEquals("No se pudo llegar", b.sinRecojo!!.textoMotivo)
        assertEquals(EstadoRecojo.PENDIENTE, filas.first { it.proveedor.id == "c" }.dia(hoy)!!.estado, "una marca deshecha vuelve a pendiente")

        val avance = avanceDelDia(filas, hoy)
        assertEquals(AvanceDelDia(asignados = 3, registrados = 0, pendientes = 2, sinRecojo = 1), avance)
        assertEquals(listOf("b"), filtrarFilas(filas, hoy, "", FiltroLista.SIN_RECOJO).map { it.proveedor.id })
        assertEquals(listOf("a", "c"), filtrarFilas(filas, hoy, "", FiltroLista.PENDIENTES).map { it.proveedor.id })
    }

    @Test
    fun `el estado de sincronizacion es independiente del estado del recojo`() {
        val filas = { remoto: Boolean ->
            construirFilasAcopio(
                ciclo, "z1", listOf(proveedorDePrueba("a"), proveedorDePrueba("b"), proveedorDePrueba("c")),
                listOf(
                    entregaDePrueba("e1", "a", 10.0, "2026-09-24T12:00:00Z", SyncState.PENDING),
                    entregaDePrueba("e2", "b", 10.0, "2026-09-24T12:00:00Z", SyncState.SYNCED),
                    entregaDePrueba("e3", "c", 10.0, "2026-09-24T12:00:00Z", SyncState.ERROR),
                ),
                emptyList(), remoto,
            ).associate { it.proveedor.id to it.dia(hoy)!!.sincronizacion }
        }
        assertEquals(
            mapOf("a" to EstadoSincronizacion.PENDIENTE, "b" to EstadoSincronizacion.SINCRONIZADO, "c" to EstadoSincronizacion.ERROR),
            filas(true),
        )
        assertEquals(EstadoSincronizacion.EN_ESTE_CELULAR, filas(false)["a"], "sin backend el dato solo está en este celular")
    }
}
