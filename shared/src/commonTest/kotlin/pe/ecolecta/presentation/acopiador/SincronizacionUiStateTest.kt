package pe.ecolecta.presentation.acopiador

import pe.ecolecta.domain.usecase.sync.ResumenColaSync
import pe.ecolecta.presentation.acopiador.sincronizacion.SincronizacionUiState
import kotlin.test.Test
import kotlin.test.assertFalse
import kotlin.test.assertTrue

class SincronizacionUiStateTest {
    @Test
    fun `cola vacia sin cuenta enlazada no anuncia exito`() {
        assertFalse(SincronizacionUiState(cargando = false).todoSincronizado)
        assertFalse(SincronizacionUiState(cuentaEnlazada = true).todoSincronizado)
    }

    @Test
    fun `solo anuncia completado con enlace y sin operaciones pendientes`() {
        val estado = SincronizacionUiState(cargando = false, cuentaEnlazada = true)
        assertTrue(estado.todoSincronizado)
        assertFalse(estado.copy(resumen = ResumenColaSync(1, 2, 0, 0)).todoSincronizado)
        assertFalse(estado.copy(resumen = ResumenColaSync(0, 2, 1, 0)).todoSincronizado)
        assertFalse(estado.copy(resumen = ResumenColaSync(0, 2, 0, 1)).todoSincronizado)
    }
}
