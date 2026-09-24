package pe.ecolecta.presentation.admin

import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.domain.model.SyncState
import pe.ecolecta.presentation.admin.entregas.EntregaDetalleUiState
import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertFalse
import kotlin.test.assertNull
import kotlin.test.assertTrue

/** Lo que ADMIN lee sobre el envío de una entrega ajena debe coincidir con lo que la app puede hacer. */
class EntregaDetalleEnvioTest {
    private fun entrega(estado: SyncState) = Entrega(
        id = "e1", jornadaId = "j1", proveedorId = "p1", usuarioId = "acop", zonaId = "z1", vehiculoId = "v1",
        litros = 20.0, tachos = 1, observaciones = null, registradoEn = 0, deviceId = "d", loteId = null,
        anulada = false, syncState = estado, syncError = "motivo", intentos = 1, updatedAt = 1,
    )

    private fun estado(sync: SyncState, remitente: String = "acop", enlazado: Boolean?) = EntregaDetalleUiState(
        cargando = false, entrega = entrega(sync), nombresUsuarios = mapOf("acop" to "Juan Pérez", "adm" to "Administrador"),
        remitenteId = remitente, remitenteEnlazado = enlazado,
    )

    @Test
    fun `sin enlace del acopiador se dice quien debe iniciar sesion y no se ofrece un reintento inutil`() {
        val s = estado(SyncState.PENDING, enlazado = false)
        val texto = s.explicacionEnvio!!
        assertTrue(texto.contains("Solo puede enviarla la cuenta de Juan Pérez (quien la registró)"), texto)
        assertTrue(texto.contains("Juan Pérez debe iniciar sesión aquí con conexión"), texto)
        assertTrue(texto.contains("ADMIN no la envía en su nombre"), texto)
        assertFalse(texto.contains("todavía no envía datos al servidor"), "el texto antiguo ya no es cierto")
        assertFalse(s.puedeReintentar)
    }

    @Test
    fun `con la cuenta enlazada se puede reintentar y un rechazo explica que corregir`() {
        val s = estado(SyncState.ERROR, enlazado = true)
        assertTrue(s.puedeReintentar)
        assertTrue(s.explicacionEnvio!!.contains("El servidor la rechazó"))
    }

    @Test
    fun `una correccion de admin la firma su propia cuenta`() {
        val s = estado(SyncState.PENDING, remitente = "adm", enlazado = false)
        assertTrue(s.explicacionEnvio!!.contains("Administrador (autor de la última corrección o anulación)"))
    }

    @Test
    fun `sin panel configurado lo dice y una entrega ya sincronizada no muestra nada`() {
        assertEquals(
            "Esta versión no tiene el panel web configurado: la entrega solo está guardada en este teléfono.",
            estado(SyncState.PENDING, enlazado = null).explicacionEnvio,
        )
        assertNull(estado(SyncState.SYNCED, enlazado = true).explicacionEnvio)
        assertFalse(estado(SyncState.SYNCED, enlazado = true).puedeReintentar)
    }
}
