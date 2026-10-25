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
import pe.ecolecta.data.auth.FirebaseAuthAnonimoProvider
import pe.ecolecta.data.local.DatabaseDriverFactory
import pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryFirebase
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
        iniciarKoin {
            androidContext(this@EcolectaApp)
            modules(
                module {
                    single { DatabaseDriverFactory(androidContext()) }
                    single<DeviceIdProvider> { DeviceIdProviderAndroid(androidContext()) }
                    single { ExportadorQr(androidContext()) }
                    // Firebase: solo Android (ver shared/build.gradle.kts). Nunca en el módulo común
                    // para que iOS siga compilando sin el SDK nativo de Firebase.
                    // RegistroAcopioRemotoRepositoryFirebase recibe la MISMA instancia de
                    // IdentidadRemotaProvider que usa Perfil — el candado contra sesiones anónimas
                    // duplicadas solo protege si todos pasan por ese único punto (ver FirebaseAuthAnonimoProvider).
                    single<IdentidadRemotaProvider> {
                        if (BuildConfig.LOCAL_PREVIEW) pe.ecolecta.data.auth.IdentidadRemotaProviderPendiente()
                        else FirebaseAuthAnonimoProvider()
                    }
                    // Vista local (-PlocalPreview=true, sin google-services.json): sin la copia de
                    // Firestore. La sincronización con el panel web sigue usando SERVIDOR_URL.
                    single<RegistroAcopioRemotoRepository> {
                        if (BuildConfig.LOCAL_PREVIEW) pe.ecolecta.data.repository.RegistroAcopioRemotoRepositoryPendiente()
                        else RegistroAcopioRemotoRepositoryFirebase(get())
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
