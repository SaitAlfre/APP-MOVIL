package pe.ecolecta.domain

import kotlinx.coroutines.async
import kotlinx.coroutines.awaitAll
import kotlinx.coroutines.test.runTest
import kotlin.test.Test
import kotlin.test.assertEquals

/**
 * Reproduce el bug real de Grupo 5: el servicio de seguimiento (dos corrutinas propias, sin
 * coordinación entre sí) y la pantalla de Perfil llaman a "asegurar sesión anónima" de forma
 * concurrente. Sin candado, cada una veía el recurso como inexistente a la vez y llamaba
 * `signInAnonymously()` por duplicado — Firebase Auth crea un usuario NUEVO en cada llamada sin
 * sesión previa, dejando uids huérfanos sin vínculo administrado (PERMISSION_DENIED en Firestore).
 */
class InicializadorUnicoTest {
    @Test
    fun `llamadas concurrentes desde seguimiento y Perfil inicializan una sola vez y obtienen el mismo valor`() = runTest {
        var vecesInicializado = 0
        // Estado mutable compartido: simula Firebase.auth.currentUser, que signInAnonymously() deja
        // establecido como efecto secundario para que las llamadas SIGUIENTES ya no lo vean null.
        var sesionActual: String? = null
        val inicializador = InicializadorUnico(
            obtenerExistente = { sesionActual },
            inicializar = {
                vecesInicializado++
                "uid-sesion-1".also { sesionActual = it }
            },
        )

        // Simula al menos 3 fuentes reales que compiten sin coordinación: la publicación inmediata
        // por cada fix GPS, el reintento periódico de 25s, y la pantalla de Perfil.
        val resultados = List(20) { async { inicializador.obtener() } }.awaitAll()

        assertEquals(1, vecesInicializado, "signInAnonymously() debe dispararse una sola vez, sin importar cuántas llamadas concurrentes")
        resultados.forEach { assertEquals("uid-sesion-1", it, "todas las llamadas deben obtener el mismo uid, no uno huérfano por duplicado") }
    }

    @Test
    fun `si ya existe el recurso, nunca llama a inicializar`() = runTest {
        var vecesInicializado = 0
        val inicializador = InicializadorUnico(
            obtenerExistente = { "uid-ya-existente" },
            inicializar = { vecesInicializado++; "uid-nuevo" },
        )

        val resultado = inicializador.obtener()

        assertEquals("uid-ya-existente", resultado)
        assertEquals(0, vecesInicializado)
    }
}
