package pe.ecolecta.presentation.admin

import pe.ecolecta.domain.model.AccionAuditoria
import pe.ecolecta.domain.model.Auditoria
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.EstadoProveedor
import pe.ecolecta.domain.model.EstadoTraslado
import pe.ecolecta.domain.model.Proveedor
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.domain.model.Usuario
import pe.ecolecta.presentation.admin.auditoria.describir
import pe.ecolecta.presentation.admin.design.TextosEstado
import pe.ecolecta.presentation.admin.entregas.EntregasUiState
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse

class TextosAdminTest {
    private val nombresInternos = SyncState.entries.map { it.name } + AccionAuditoria.entries.map { it.name } + EstadoTraslado.entries.map { it.name }

    @Test
    fun `ningun estado se muestra con su nombre interno en ingles`() {
        SyncState.entries.forEach { estado ->
            listOf(TextosEstado.corta(estado), TextosEstado.larga(estado), TextosEstado.ayuda(estado)).forEach { texto ->
                assertFalse(nombresInternos.any { texto.contains(it) }, "«$texto» muestra un nombre interno")
            }
        }
        AccionAuditoria.entries.forEach { assertFalse(TextosEstado.accion(it) in nombresInternos) }
        EstadoTraslado.entries.forEach { assertFalse(TextosEstado.traslado(it) in nombresInternos) }
    }

    @Test
    fun `etiquetas pedidas para los estados de sincronizacion`() {
        assertEquals(
            listOf("Pendiente de sincronización", "Sincronizando", "Sincronizada", "Error de sincronización", "Conflicto por resolver"),
            SyncState.entries.map(TextosEstado::larga),
        )
    }

    private fun entrega(id: String, estado: SyncState, anulada: Boolean = false) = Entrega(
        id = id, jornadaId = "j", proveedorId = "p1", usuarioId = "u", zonaId = "z", vehiculoId = "v", litros = 24.0, tachos = 1,
        observaciones = null, registradoEn = 0, deviceId = "d", loteId = null, anulada = anulada, syncState = estado,
        syncError = null, intentos = 0, updatedAt = 0,
    )

    @Test
    fun `una anulada solo aparece en el filtro de anuladas`() {
        val estado = EntregasUiState(entregas = listOf(entrega("a", SyncState.PENDING, anulada = true), entrega("b", SyncState.PENDING)))

        assertEquals(listOf("b"), estado.copy(filtroSyncState = SyncState.PENDING).entregasFiltradas.map { it.id })
        assertEquals(listOf("a"), estado.copy(soloAnuladas = true).entregasFiltradas.map { it.id })
        assertEquals(2, estado.entregasFiltradas.size)
    }

    @Test
    fun `la auditoria dice quien, sobre que registro y los valores`() {
        val registro = Auditoria(
            id = "a1", entidad = "entrega", entidadId = "e1", accion = AccionAuditoria.CORREGIR,
            valorAntes = "litros=24.0;tachos=1", valorDespues = "litros=21.0;tachos=1", motivo = "Recuento",
            usuarioId = "admin", ocurridoEn = 0, deviceId = "d", syncState = SyncState.PENDING,
        )
        val admin = Usuario("admin", "admin", "Administrador General", "00000001", "h", "s", true, listOf(Rol.ADMIN), 0)
        val proveedor = Proveedor(
            "p1", "PRV-004", "Luis Apaza", "10000004", null, null, "z", 2, 40.0, EstadoProveedor.ACTIVO, 0, SyncState.SYNCED,
        )

        val descrito = describir(listOf(registro), listOf(admin), listOf(proveedor), listOf(entrega("e1", SyncState.PENDING))).single()

        assertEquals("Administrador General (@admin)", descrito.autor)
        assertEquals("Entrega de Luis Apaza (24 L)", descrito.sobre)
        assertEquals("litros 24.0 · tachos 1", TextosEstado.valores(registro.valorAntes!!))
        assertEquals("Corrección", TextosEstado.accion(registro.accion))
    }
}
