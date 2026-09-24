package pe.ecolecta.presentation

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.lifecycle.ViewModelStore
import androidx.lifecycle.ViewModelStoreOwner
import androidx.lifecycle.viewmodel.compose.LocalViewModelStoreOwner
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.collectLatest
import kotlinx.coroutines.launch
import org.koin.compose.koinInject
import pe.ecolecta.domain.model.Rol
import pe.ecolecta.domain.model.Sesion
import pe.ecolecta.domain.usecase.auth.CerrarSesionUseCase
import pe.ecolecta.domain.usecase.auth.ObtenerSesionUseCase
import pe.ecolecta.domain.usecase.jornada.InicioAcopiador
import pe.ecolecta.domain.usecase.jornada.ResolverInicioAcopiadorUseCase
import pe.ecolecta.domain.usecase.proveedor.ObtenerPerfilProveedorUseCase
import pe.ecolecta.domain.usecase.sync.SincronizarRegistrosAcopioUseCase
import pe.ecolecta.presentation.acopiador.AcopiadorShell
import pe.ecolecta.presentation.acopiador.onboarding.SeleccionZonaVehiculoScreen
import pe.ecolecta.presentation.admin.AdminShell
import pe.ecolecta.presentation.auth.LoginScreen
import pe.ecolecta.presentation.auth.SeleccionRolScreen
import pe.ecolecta.presentation.calidad.CalidadShell
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.EcolectaTheme
import pe.ecolecta.presentation.design.EscenarioAnimado
import pe.ecolecta.presentation.navegacion.Pantalla
import pe.ecolecta.presentation.proveedor.ProveedorShell

private const val INTERVALO_SINCRONIZACION_MS = 15_000L

/** Nunca coincide con un usuario.id real (son UUID) — marca "sin sesión activa". */
private const val CLAVE_SIN_SESION = "sin-sesion"

/**
 * Clave de la que depende el ciclo de vida de los ViewModels de sesión (ver
 * [rememberSesionViewModelStoreOwner]). Expuesta `internal` para poder probarla sin necesidad de
 * infraestructura de test de Compose, que este repo no tiene.
 */
internal fun claveSesionDe(sesion: Sesion?): String = sesion?.usuario?.id ?: CLAVE_SIN_SESION

/**
 * Pantalla con la que arranca el ACOPIADOR. Una jornada abierta o ya terminada hoy van al inicio
 * (que muestra la activa o el resumen de la terminada); sin jornada, a la selección, que explica
 * por sí misma el caso "sin zonas activas". `internal` para probarla sin Compose.
 */
internal fun pantallaInicialAcopiador(inicio: InicioAcopiador): Pantalla = when (inicio) {
    is InicioAcopiador.JornadaAbierta, is InicioAcopiador.JornadaTerminadaHoy -> Pantalla.AcopiadorHome
    InicioAcopiador.SinZonaActiva, InicioAcopiador.SeleccionarZonaVehiculo -> Pantalla.AcopiadorSeleccionZonaVehiculo
}

/**
 * `koinViewModel()` resuelve cada ViewModel contra el [ViewModelStoreOwner] más cercano en la
 * composición — por defecto, el de la Activity, que es único durante toda la vida del proceso. Sin
 * esto, cambiar de sesión (cerrar sesión y volver a entrar, con el mismo usuario o con otro, SIN
 * matar el proceso) no crea ViewModels nuevos: los de la sesión anterior siguen vivos, con el estado
 * que capturaron en su `init` (p. ej. [pe.ecolecta.presentation.acopiador.perfil.PerfilViewModel] lee
 * `nombres`/`username` una sola vez al construirse y nunca vuelve a mirar la sesión).
 *
 * La solución: en cada transición de sesión, todo el árbol de pantallas recibe un
 * [ViewModelStoreOwner] nuevo, y el anterior se limpia (`ViewModelStore.clear()` llama a
 * `onCleared()` en cada ViewModel vivo, cancelando su `viewModelScope` — y con él, cualquier
 * `collect`/observador que ese ViewModel tuviera corriendo). [claveSesionDe] siempre pasa por
 * [CLAVE_SIN_SESION] entre dos sesiones reales (el flujo de login siempre cierra sesión antes de
 * abrir una nueva), así que dos transiciones consecutivas nunca comparten clave — ni siquiera al
 * volver a entrar como el mismo usuario.
 */
@Composable
private fun rememberSesionViewModelStoreOwner(claveSesion: String): ViewModelStoreOwner {
    val owner = remember(claveSesion) {
        object : ViewModelStoreOwner {
            override val viewModelStore = ViewModelStore()
        }
    }
    DisposableEffect(claveSesion) {
        onDispose { owner.viewModelStore.clear() }
    }
    return owner
}

@Composable
fun App() {
    EcolectaTheme {
        var pantalla by remember { mutableStateOf<Pantalla>(Pantalla.Splash) }
        var claveSesion by remember { mutableStateOf(CLAVE_SIN_SESION) }
        val obtenerSesionUseCase = koinInject<ObtenerSesionUseCase>()
        val cerrarSesionUseCase = koinInject<CerrarSesionUseCase>()
        val resolverInicioAcopiadorUseCase = koinInject<ResolverInicioAcopiadorUseCase>()
        val obtenerPerfilProveedorUseCase = koinInject<ObtenerPerfilProveedorUseCase>()
        val sincronizarRegistros = koinInject<SincronizarRegistrosAcopioUseCase>()
        val sincronizarDatos = koinInject<pe.ecolecta.domain.usecase.sync.SincronizarDatosServidorUseCase>()
        val scope = rememberCoroutineScope()

        // Envía entregas y "sin recojo" pendientes de ESTE celular mientras la app está abierta, con o
        // sin sesión (lo guardado no debe quedarse atascado por cerrar sesión). Sin backend configurado
        // no hace nada. Cada documento usa el id del registro: reintentar nunca duplica.
        // Después trae lo que cambió en el panel web (cuentas, catálogos, entregas, calidad, comunicados)
        // con la cuenta en sesión: primero se envía, así lo aún no enviado nunca se pisa.
        LaunchedEffect(Unit) {
            while (true) {
                runCatching { sincronizarRegistros() }
                runCatching { sincronizarDatos() }
                delay(INTERVALO_SINCRONIZACION_MS)
            }
        }

        LaunchedEffect(Unit) {
            obtenerSesionUseCase().collectLatest { sesion ->
                claveSesion = claveSesionDe(sesion)
                when {
                    sesion == null -> pantalla = Pantalla.Login
                    sesion.rolActivo == Rol.ADMIN -> pantalla = Pantalla.AdminDashboard
                    sesion.rolActivo == Rol.ACOPIADOR -> {
                        pantalla = pantallaInicialAcopiador(resolverInicioAcopiadorUseCase(sesion.usuario.id))
                    }
                    sesion.rolActivo == Rol.PROVEEDOR -> {
                        val proveedor = obtenerPerfilProveedorUseCase(sesion.usuario.id)
                        pantalla = if (proveedor != null) Pantalla.ProveedorHome else Pantalla.RolNoDisponible
                    }
                    sesion.rolActivo == Rol.CALIDAD -> pantalla = Pantalla.CalidadInicio
                    else -> pantalla = Pantalla.RolNoDisponible
                }
            }
        }

        fun cerrarSesion() {
            scope.launch { cerrarSesionUseCase() }
        }

        val sesionViewModelStoreOwner = rememberSesionViewModelStoreOwner(claveSesion)
        CompositionLocalProvider(LocalViewModelStoreOwner provides sesionViewModelStoreOwner) {
            key(claveSesion) {
                Box(Modifier.fillMaxSize().background(Colores.bgBase)) { EscenarioAnimado(pantalla) {
                    when (val actual = pantalla) {
                        Pantalla.Splash -> SplashScreen()
                        Pantalla.Login -> LoginScreen(
                            alSesionIniciada = {},
                            alRequerirSeleccionRol = { usuarioId -> pantalla = Pantalla.SeleccionRol(usuarioId) },
                        )
                        is Pantalla.SeleccionRol -> SeleccionRolScreen(
                            usuarioId = actual.usuarioId,
                            alRolSeleccionado = {},
                        )
                        Pantalla.RolNoDisponible -> RolNoDisponibleScreen(alCerrarSesion = ::cerrarSesion)
                        Pantalla.AcopiadorSeleccionZonaVehiculo -> SeleccionZonaVehiculoScreen(
                            alJornadaAbierta = { pantalla = Pantalla.AcopiadorHome },
                        )
                        Pantalla.AcopiadorHome,
                        Pantalla.AcopiadorLista,
                        Pantalla.AcopiadorEntregas,
                        Pantalla.AcopiadorResumen,
                        is Pantalla.AcopiadorRegistroEntrega,
                        Pantalla.AcopiadorEscanearQr,
                        Pantalla.AcopiadorLote,
                        Pantalla.AcopiadorSincronizacion,
                        Pantalla.AcopiadorPerfil,
                        -> AcopiadorShell(pantalla = actual, onCambiarPantalla = { pantalla = it })
                        Pantalla.ProveedorHome,
                        Pantalla.ProveedorEntregas,
                        is Pantalla.ProveedorEntregaDetalle,
                        Pantalla.ProveedorMiCiclo,
                        Pantalla.ProveedorMiQr,
                        Pantalla.ProveedorPerfil,
                        Pantalla.ProveedorCalidad,
                        Pantalla.ProveedorPagos,
                        Pantalla.ProveedorReclamos,
                        Pantalla.ProveedorTraslado,
                        Pantalla.ProveedorSolicitudes,
                        -> ProveedorShell(pantalla = actual, onCambiarPantalla = { pantalla = it })
                        Pantalla.CalidadInicio,
                        Pantalla.CalidadNuevo,
                        -> CalidadShell(
                            pantalla = actual,
                            onCambiarPantalla = { pantalla = it },
                            onCerrarSesion = ::cerrarSesion,
                        )
                        else -> AdminShell(
                            pantalla = actual,
                            onCambiarPantalla = { pantalla = it },
                            onCerrarSesion = ::cerrarSesion,
                        )
                    }
                } }
            }
        }
    }
}
