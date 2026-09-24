package pe.ecolecta.presentation.acopiador

import pe.ecolecta.presentation.acopiador.perfil.EstadoServidor
import pe.ecolecta.presentation.acopiador.perfil.estadoServidor
import kotlin.test.Test
import kotlin.test.assertEquals

/** El Perfil ya no dice "Conectado" fijo: muestra el estado real de la sincronización. */
class EstadoServidorPerfilTest {
    @Test
    fun `el estado del servidor refleja la realidad del celular`() {
        assertEquals("Sin sincronización disponible", estadoServidor(vinculado = false, pendientes = 3).texto)
        assertEquals("2 por enviar", estadoServidor(vinculado = true, pendientes = 2).texto)
        assertEquals(EstadoServidor.AL_DIA, estadoServidor(vinculado = true, pendientes = 0))
    }
}
