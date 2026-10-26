package pe.ecolecta.data

import app.cash.sqldelight.db.SqlDriver
import app.cash.sqldelight.driver.jdbc.sqlite.JdbcSqliteDriver
import kotlinx.coroutines.*
import kotlinx.coroutines.flow.*
import org.koin.dsl.koinApplication
import org.koin.dsl.module
import pe.ecolecta.data.local.db.EcolectaDatabase
import pe.ecolecta.data.remote.ServidorWebNoConfigurado
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente
import pe.ecolecta.di.dataModule
import pe.ecolecta.di.domainModule
import pe.ecolecta.domain.model.*
import pe.ecolecta.domain.repository.*
import pe.ecolecta.domain.usecase.sync.CoordinadorSync
import kotlin.test.*

class CoordinadorSyncTest {
    private val usuario = Usuario("u1", "acopio", "Acopiador", "12345678", "", "", true, listOf(Rol.ACOPIADOR), 0)
    private class Panel : ServidorWebRepository by ServidorWebNoConfigurado() {
        override val configurado = true
        var error: Exception? = null
        var version = 1L
        var esperar: CompletableDeferred<Unit>? = null
        val entro = CompletableDeferred<Unit>()
        override suspend fun descargarDatos(usuarioId: String): Result<DatosServidor> {
            entro.complete(Unit)
            esperar?.await()
            return error?.let { Result.failure(it) } ?: Result.success(DatosServidor(
                servidorEn = version,
                comunicados = listOf(ComunicadoServidor(1, "Aviso", "Versión $version", "Admin", version)),
            ))
        }
    }
    private fun entorno(panel: Panel) = koinApplication {
        allowOverride(true)
        modules(dataModule, domainModule, module {
            single<SqlDriver> { JdbcSqliteDriver(JdbcSqliteDriver.IN_MEMORY).also { EcolectaDatabase.Schema.create(it) } }
            single<ServidorWebRepository> { panel }
            single<RegistroAcopioRemotoRepository> { RegistroAcopioRemotoRepositoryPendiente() }
            single<SesionRepository> { object : SesionRepository {
                override fun observar() = flowOf(Sesion(usuario, Rol.ACOPIADOR))
                override suspend fun iniciar(sesion: Sesion) = Unit
                override suspend fun cerrar() = Unit
            } }
        })
    }

    @Test fun `manual descarga y confirma y una desconexion posterior quita el verde`() = runBlocking {
        val panel = Panel()
        val app = entorno(panel)
        try {
            val sync = app.koin.get<CoordinadorSync>()
            assertEquals("Sincronización completada", sync.ejecutar(manual = true))
            assertTrue(sync.estado.value.verificado)
            val ultima = sync.estado.value.ultimaSync
            assertNotNull(ultima)
            panel.error = SinConexionRemotaException()
            sync.ejecutar(manual = true)
            assertTrue(sync.estado.value.sinConexion)
            assertFalse(sync.estado.value.verificado)
            assertEquals(ultima, sync.estado.value.ultimaSync)
            panel.error = null
            sync.ejecutar(manual = true)
            assertTrue(sync.estado.value.verificado)
            assertFalse(sync.estado.value.sinConexion)
        } finally { app.close() }
    }

    @Test fun `modo manual detecta novedades sin aplicarlas y persiste la preferencia`() = runBlocking {
        val panel = Panel()
        val app = entorno(panel)
        try {
            val sync = app.koin.get<CoordinadorSync>()
            sync.ejecutar(manual = true)
            sync.automatico(false)
            val db = app.koin.get<EcolectaDatabase>()
            assertEquals(0L, db.preferenciaSyncQueries.obtener("u1").executeAsOne().automatico)
            val ultima = sync.estado.value.ultimaSync
            val reiniciado = CoordinadorSync(app.koin.get(), app.koin.get(), app.koin.get(), app.koin.get(),
                app.koin.get(), app.koin.get(), app.koin.get(), app.koin.get(), app.koin.get(), app.koin.get())
            reiniciado.ejecutar()
            assertFalse(reiniciado.estado.value.novedades)
            assertFalse(reiniciado.estado.value.automatico)
            panel.version = 2
            sync.ejecutar()
            assertTrue(sync.estado.value.novedades)
            assertEquals(ultima, sync.estado.value.ultimaSync)
            assertTrue(db.comunicadoQueries.selectTodos().executeAsList().single().mensaje.contains("Versión 1"))
            sync.ejecutar(manual = true)
            assertTrue(db.comunicadoQueries.selectTodos().executeAsList().single().mensaje.contains("Versión 2"))
            assertFalse(sync.estado.value.novedades)
            assertTrue(sync.estado.value.verificado)
            assertFalse(sync.estado.value.automatico)
        } finally { app.close() }
    }

    @Test fun `boton y gesto no duplican una operacion en curso`() = runBlocking {
        val panel = Panel().apply { esperar = CompletableDeferred() }
        val app = entorno(panel)
        try {
            val sync = app.koin.get<CoordinadorSync>()
            val primera = async { sync.ejecutar(manual = true) }
            panel.entro.await()
            assertTrue(sync.estado.value.sincronizando)
            assertNull(sync.ejecutar(manual = true))
            panel.esperar!!.complete(Unit)
            primera.await()
            assertFalse(sync.estado.value.sincronizando)
            assertTrue(sync.estado.value.verificado)
        } finally { app.close() }
    }
}
