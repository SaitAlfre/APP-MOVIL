package pe.ecolecta.presentation.acopiador

import pe.ecolecta.domain.model.SyncState
import kotlin.test.Test
import kotlin.test.assertEquals

class ChipSyncAcopiadorTest {
    @Test
    fun `las etiquetas de sincronizacion son comprensibles para el acopiador`() {
        assertEquals("Pendiente de envío", etiquetaSyncAcopiador(SyncState.PENDING))
        assertEquals("Enviando…", etiquetaSyncAcopiador(SyncState.SYNCING))
        assertEquals("Sincronizada", etiquetaSyncAcopiador(SyncState.SYNCED))
        assertEquals("Error al enviar", etiquetaSyncAcopiador(SyncState.ERROR))
        assertEquals("Requiere revisión", etiquetaSyncAcopiador(SyncState.CONFLICT))
    }
}
