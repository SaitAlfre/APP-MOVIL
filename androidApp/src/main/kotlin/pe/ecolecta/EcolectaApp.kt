package pe.ecolecta

import android.app.Application
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch
import org.koin.android.ext.koin.androidContext
import org.koin.dsl.module
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.di.iniciarKoin
import pe.ecolecta.di.moduloServidorWeb
import pe.ecolecta.domain.DeviceIdProvider
import pe.ecolecta.domain.DeviceIdProviderAndroid
import pe.ecolecta.domain.IdentidadRemotaProvider
import pe.ecolecta.domain.repository.RegistroAcopioRemotoRepository
import pe.ecolecta.presentation.qr.ExportadorQr
import qrgenerator.AppContext

class EcolectaApp : Application() {
    enum class EstadoInicio { PREPARANDO, LISTO, ERROR }

    private val scopeInicio = CoroutineScope(SupervisorJob() + Dispatchers.Default)
    private val _estadoInicio = MutableStateFlow(EstadoInicio.PREPARANDO)
    val estadoInicio = _estadoInicio.asStateFlow()

    override fun onCreate() {
        super.onCreate()
        AppContext.apply { set(applicationContext) }
        // El sembrado inicial deriva hashes PBKDF2: no debe bloquear Application.onCreate.
        // MainActivity espera LISTO antes de crear pantallas que consultan Koin o la base.
        scopeInicio.launch {
            try {
                prepararDatos()
                _estadoInicio.value = EstadoInicio.LISTO
            } catch (error: Exception) {
                android.util.Log.e("EcolectaInicio", "No se pudo preparar la aplicación", error)
                _estadoInicio.value = EstadoInicio.ERROR
            }
        }
    }

    private fun prepararDatos() {
        iniciarKoin(datosDemostracion = BuildConfig.LOCAL_PREVIEW) {
            androidContext(this@EcolectaApp)
            modules(
                module {
                    single { DatabaseDriverFactory(androidContext()) }
                    single<DeviceIdProvider> { DeviceIdProviderAndroid(androidContext()) }
                    single { ExportadorQr(androidContext()) }
                    single<IdentidadRemotaProvider> { pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente() }
                    single<RegistroAcopioRemotoRepository> {
                        pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryApi(
                            get(), get(), get(), get(), get(), get(), get(), get(), get(),
                            estado = { get<pe.ecolecta.domain.usecase.sync.CoordinadorSync>().estado },
                        )
                    }
                },
                // Panel web: fuente oficial de entregas y liquidaciones. Sin URL al compilar, la app lo
                // dice ("panel web no configurado") en vez de fingir que sincroniza.
                moduloServidorWeb(
                    urlBase = BuildConfig.SERVIDOR_URL,
                    nombreDispositivo = "${android.os.Build.MANUFACTURER} ${android.os.Build.MODEL}".take(120),
                    io = kotlinx.coroutines.Dispatchers.IO,
                ),
            )
        }
    }
}
