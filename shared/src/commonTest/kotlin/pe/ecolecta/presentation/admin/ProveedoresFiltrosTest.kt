package pe.ecolecta.presentation.admin

import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Zona
import pe.ecolecta.presentation.admin.proveedores.ProveedorFila
import pe.ecolecta.presentation.admin.proveedores.ProveedoresUiState
import kotlin.test.Test
import kotlin.test.assertEquals

class ProveedoresFiltrosTest {
    private fun fila(codigo: String, nombre: String, zona: String, estado: EstadoProveedor, cuenta: String?) = ProveedorFila(
        Proveedor(
            id = codigo, codigo = codigo, nombres = nombre, dni = "1000000${codigo.last()}", telefono = null, direccion = null,
            zonaId = zona, tachos = 2, capacidadTachoL = 40.0, estado = estado, updatedAt = 0, syncState = SyncState.SYNCED,
            usuarioId = cuenta,
        ),
        zona = zona, cuenta = cuenta, cuentaActiva = cuenta != null,
    )

    private val base = ProveedoresUiState(
        cargando = false,
        zonas = listOf(Zona("faon", "FAON", true), Zona("moro", "MORO", true)),
        filas = listOf(
            fila("PRV-1", "Mario Quispe", "faon", EstadoProveedor.ACTIVO, "mquispe"),
            fila("PRV-2", "Elena Huamán", "faon", EstadoProveedor.SUSPENDIDO, null),
            fila("PRV-3", "Rosa Ccapa", "moro", EstadoProveedor.RETIRADO, null),
            fila("PRV-4", "Luis Apaza", "moro", EstadoProveedor.ACTIVO, null),
        ),
    )

    private fun ProveedoresUiState.codigos() = visibles.map { it.proveedor.codigo }

    @Test
    fun `al abrir se ven todos los estados, incluidos suspendidos y retirados`() {
        assertEquals(null, ProveedoresUiState().estado)
        assertEquals(listOf("PRV-1", "PRV-2", "PRV-3", "PRV-4"), base.codigos())
    }

    @Test
    fun `busqueda y estado se combinan`() {
        assertEquals(listOf("PRV-4"), base.copy(texto = "a", estado = EstadoProveedor.ACTIVO, zonaId = "moro").codigos())
        assertEquals(listOf("PRV-2"), base.copy(texto = "huam", estado = EstadoProveedor.SUSPENDIDO).codigos())
    }

    @Test
    fun `zona y sin cuenta se combinan`() {
        assertEquals(listOf("PRV-2"), base.copy(zonaId = "faon", sinCuenta = true).codigos())
        assertEquals(listOf("PRV-3", "PRV-4"), base.copy(zonaId = "moro", sinCuenta = true).codigos())
    }

    @Test
    fun `estado y sin cuenta se combinan`() {
        assertEquals(listOf("PRV-4"), base.copy(estado = EstadoProveedor.ACTIVO, sinCuenta = true).codigos())
    }

    @Test
    fun `sin coincidencias explica que filtros estan aplicados`() {
        val vacio = base.copy(texto = "vila", zonaId = "moro", estado = EstadoProveedor.RETIRADO, sinCuenta = true)
        assertEquals(emptyList(), vacio.codigos())
        assertEquals("búsqueda «vila», MORO, Retirado, sin cuenta", vacio.resumenFiltros)
        assertEquals("ninguno", base.resumenFiltros)
    }

    @Test
    fun `una ficha que recibe cuenta deja de contar como sin cuenta`() {
        val vinculada = base.copy(filas = base.filas.map { if (it.proveedor.codigo == "PRV-2") it.copy(cuenta = "prv_2", cuentaActiva = true) else it })
        assertEquals(listOf("PRV-3", "PRV-4"), vinculada.copy(sinCuenta = true).codigos())
    }
}
